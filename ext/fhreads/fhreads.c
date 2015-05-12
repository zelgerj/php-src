/*
  +----------------------------------------------------------------------+
  | PHP Version 5                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2015 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author: Johann Zelger <jz@appserver.io>                              |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

#ifdef HAVE_CONFIG_H
# include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_fhreads.h"

/* If you declare any globals in php_fhreads.h uncomment this:
ZEND_DECLARE_MODULE_GLOBALS(fhreads)
*/

/* True global resources - no need for thread safety here */
static int le_fhreads;

/* {{{ PHP_INI
 */
/* Remove comments and fill if you need to have entries in php.ini
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("fhreads.global_value",      "42", PHP_INI_ALL, OnUpdateLong, global_value, zend_fhreads_globals, fhreads_globals)
    STD_PHP_INI_ENTRY("fhreads.global_string", "foobar", PHP_INI_ALL, OnUpdateString, global_string, zend_fhreads_globals, fhreads_globals)
PHP_INI_END()
*/
/* }}} */

void *fhread_routine (void *arg)
{
	/* passed the object as argument */
	FHREAD *fhread;
	fhread = (FHREAD *) arg;

	void ***c_tsrm_ls = fhread->cls;

	/* init threadsafe manager local storage */
	void ***tsrm_ls = NULL;

	/* create new context */
	fhread->tls = tsrm_ls = tsrm_new_interpreter_context();

	/* set interpreter context */
	tsrm_set_interpreter_context(tsrm_ls);

	/* set context the same as parent */
	SG(server_context) = FHREADS_SG(c_tsrm_ls, server_context);

	/* some php globals */
	PG(expose_php) = 0;
	PG(auto_globals_jit) = 0;

	/* request startup */
	php_request_startup(TSRMLS_C);

	/*
	CG(class_table) = FHREADS_CG(c_tsrm_ls, class_table);
	CG(filenames_table) = FHREADS_CG(c_tsrm_ls, filenames_table);
	EG(active_symbol_table) = &FHREADS_EG(c_tsrm_ls, symbol_table);
	 */

	EG(objects_store) = FHREADS_EG(c_tsrm_ls, objects_store);

	zval **aa;
	zend_hash_find(&FHREADS_EG(c_tsrm_ls, symbol_table), "a", sizeof("a"), (void**)&aa);

	/*
	MAKE_STD_ZVAL(*aa);
	Z_TYPE_P(*aa) = IS_OBJECT;
	Z_OBJ_HANDLE_P(*aa) = handle;
	*/

	ZEND_SET_SYMBOL(&EG(symbol_table), "a", *aa);

	zend_eval_string("include 'ext/fhreads/runnable.php';", NULL, "thevs1" TSRMLS_CC);

	/* shutdown request */
	// php_request_shutdown(TSRMLS_C);

	/* free interpreter */
	// tsrm_free_interpreter_context(tsrm_ls);

	pthread_exit(NULL);

#ifdef _WIN32
	return NULL; /* silence MSVC compiler */
#endif
}

/* {{{ proto fhread_self()
	Obtain the identifier of the current thread. */
PHP_FUNCTION(fhread_self)
{
	ZVAL_LONG(return_value, fthread_self());
}

/* {{{ proto fhread_create()
   Create a new thread, starting with execution of START-ROUTINE
   getting passed ARG.  Creation attributed come from ATTR.  The new
   handle is stored in thread_id which will be returned to php userland. */
PHP_FUNCTION(fhread_create)
{
	pthread_t thread_id;
	void *thread_result;
	int status;
	FHREAD *fhread = malloc(sizeof(FHREAD));

	// setup thread args for fhread routine
	fhread->tls = TSRMLS_C;
	fhread->cid = pthread_self();

	status = pthread_create(&fhread->tid, NULL, fhread_routine, (void *) &fhread);

	RETURN_LONG((long)fhread->cid);
}

/* {{{ proto fhread_join()
   Make calling thread wait for termination of the given thread id.  The
   exit status of the thread is stored in *THREAD_RETURN, if THREAD_RETURN
   is not NULL.*/
PHP_FUNCTION(fhread_join)
{
	long thread_id;
	int status;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &thread_id) == FAILURE) {
		RETURN_NULL();
	}

	status = pthread_join((pthread_t)thread_id, NULL);

	RETURN_LONG((long)status);
}

/* {{{ php_fhreads_init_globals
 */
/* Uncomment this function if you have INI entries
static void php_fhreads_init_globals(zend_fhreads_globals *fhreads_globals)
{
	fhreads_globals->global_value = 0;
	fhreads_globals->global_string = NULL;
}
*/
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(fhreads)
{
	/* If you have INI entries, uncomment these lines 
	REGISTER_INI_ENTRIES();
	*/
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(fhreads)
{
	/* uncomment this line if you have INI entries
	UNREGISTER_INI_ENTRIES();
	*/
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request start */
/* {{{ PHP_RINIT_FUNCTION
 */
PHP_RINIT_FUNCTION(fhreads)
{
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request end */
/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(fhreads)
{
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(fhreads)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "fhreads support", "enabled");
	php_info_print_table_end();

	/* Remove comments if you have entries in php.ini
	DISPLAY_INI_ENTRIES();
	*/
}
/* }}} */

/* {{{ fhreads_functions[]
 *
 * Every user visible function must have an entry in fhreads_functions[].
 */
const zend_function_entry fhreads_functions[] = {
	PHP_FE(fhread_create, 	NULL)
	PHP_FE(fhread_join, 	NULL)
	PHP_FE(fhread_self, 	NULL)
	PHP_FE_END	/* Must be the last line in fhreads_functions[] */
};
/* }}} */

/* {{{ fhreads_module_entry
 */
zend_module_entry fhreads_module_entry = {
	STANDARD_MODULE_HEADER,
	"fhreads",
	fhreads_functions,
	PHP_MINIT(fhreads),
	PHP_MSHUTDOWN(fhreads),
	PHP_RINIT(fhreads),		/* Replace with NULL if there's nothing to do at request start */
	PHP_RSHUTDOWN(fhreads),	/* Replace with NULL if there's nothing to do at request end */
	PHP_MINFO(fhreads),
	PHP_FHREADS_VERSION,
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_FHREADS
ZEND_GET_MODULE(fhreads)
#endif

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
