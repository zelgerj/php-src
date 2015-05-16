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
static void ***g_tsrm_ls;

zend_object_handlers fhreads_handlers;
zend_object_handlers *fhreads_zend_handlers;

/* {{{ PHP_INI
 */
/* Remove comments and fill if you need to have entries in php.ini
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("fhreads.global_value",      "42", PHP_INI_ALL, OnUpdateLong, global_value, zend_fhreads_globals, fhreads_globals)
    STD_PHP_INI_ENTRY("fhreads.global_string", "foobar", PHP_INI_ALL, OnUpdateString, global_string, zend_fhreads_globals, fhreads_globals)
PHP_INI_END()
*/
/* }}} */

void fhread_write_property(zval *object, zval *member, zval *value, const zend_literal *key TSRMLS_DC)
{
	//Z_ADDREF_P(value);
	zend_std_write_property(object, member, value, key TSRMLS_CC);
}

void *fhread_routine (void *arg)
{
	// passed the object as argument
	FHREAD* fhread = (FHREAD *) arg;

	// init threadsafe manager local storage and create new context
	TSRMLS_D = fhread->tsrm_ls;

	// set interpreter context
	tsrm_set_interpreter_context(TSRMLS_C);

	// init executor
	init_executor(TSRMLS_C);

	/* save context based stuff */
	zend_objects_store fhread_objects_store = EG(objects_store);
	HashTable fhread_regular_list = EG(regular_list);
	HashTable fhread_symbol_table = EG(symbol_table);
	HashTable *fhread_function_table = EG(function_table);
	HashTable *fhread_class_table = EG(class_table);
	HashTable *fhread_zend_constants = EG(zend_constants);
	zend_op_array *fhread_active_op_array = EG(active_op_array);

	// link server context
	SG(server_context) = FHREADS_SG(fhread->c_tsrm_ls, server_context);

	// link functions
	EG(function_table) = FHREADS_CG(fhread->c_tsrm_ls, function_table);
	// link classes
	EG(class_table) = FHREADS_CG(fhread->c_tsrm_ls, class_table);
	// link constants
	EG(zend_constants) = FHREADS_EG(fhread->c_tsrm_ls, zend_constants);
	// link regular list
	EG(regular_list) = FHREADS_EG(fhread->c_tsrm_ls, regular_list);
	// link objects_store
	EG(objects_store) = FHREADS_EG(fhread->c_tsrm_ls, objects_store);

	// declair runnable zval
	zval **runnable;
	// get runnable from creator symbol table
	zend_hash_find(&FHREADS_EG(fhread->c_tsrm_ls, symbol_table), fhread->gid, fhread->gid_len + 1, (void**)&runnable);

	// inject fhread handlers for runnable zval
	Z_OBJ_HT_PP(runnable) = &fhreads_handlers;

	zend_function *fun;
	zend_hash_find(&Z_OBJCE_PP(runnable)->function_table, "run", sizeof("run"), (void**)&fun);

	EG(This) = (*runnable);
	EG(active_op_array) = (zend_op_array*) fun;
	EG(scope) = Z_OBJCE_PP(runnable);
	EG(called_scope) = EG(scope);
	EG(in_execution) = 1;

	zend_execute((zend_op_array*) fun TSRMLS_CC);

	// call run method
	//zend_call_method(runnable, Z_OBJCE_PP(runnable), NULL, ZEND_STRL("run"), NULL, 0, NULL, NULL TSRMLS_CC);
	// zend_call_method(runnable, zend_get_class_entry(&(**runnable), fhread->c_tsrm_ls), NULL, ZEND_STRL("run"), NULL, 0, NULL, NULL, fhread->c_tsrm_ls);

	// reset to thread context based stuff for shutdown properly
	EG(objects_store) = fhread_objects_store;
	EG(active_op_array) = fhread_active_op_array;
	EG(regular_list) = fhread_regular_list;
	EG(symbol_table) = fhread_symbol_table;
	EG(function_table) = fhread_function_table;
	EG(class_table) = fhread_class_table;
	EG(zend_constants) = fhread_zend_constants;


	// shutdown executor
	shutdown_executor(TSRMLS_C);

	// free interpreter context
	tsrm_free_interpreter_context(TSRMLS_C);

	// free fhread args
	free(fhread);

	// exit thread
	pthread_exit(NULL);

#ifdef _WIN32
	return NULL; /* silence MSVC compiler */
#endif
}

/* {{{ proto fhread_tls_get_id()
	Obtain the identifier of currents ls context */
PHP_FUNCTION(fhread_tls_get_id)
{
	ZVAL_LONG(return_value, (long)TSRMLS_C);
}

/* {{{ proto fhread_object_get_handle(object obj)
	Obtain the identifier of the given objects handle */
PHP_FUNCTION(fhread_object_get_handle)
{
	zval *obj;
	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "z", &obj) == FAILURE) {
		RETURN_NULL();
	}
	ZVAL_LONG(return_value, Z_OBJ_HANDLE_P(obj));
}

/* {{{ proto fhread_self()
	Obtain the identifier of the current thread. */
PHP_FUNCTION(fhread_self)
{
	ZVAL_LONG(return_value, tsrm_thread_id());
}

/* {{{ proto fhread_free_interpreter_context(long thread_id)
	frees an interpreter context created by a thread */
PHP_FUNCTION(fhread_free_interpreter_context)
{
	THREAD_T thread_id;
	int status;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &thread_id) == FAILURE) {
		RETURN_NULL();
	}

	//void ***f_tsrm_ls = (void ***) ts_resource_ex(0, &thread_id);
	//printf("fhread_free_interpreter_context: %d\n", f_tsrm_ls);
	// tsrm_free_interpreter_context(f_tsrm_ls);
}

/* {{{ proto fhread_create()
   Create a new thread, starting with execution of START-ROUTINE
   getting passed ARG.  Creation attributed come from ATTR.  The new
   handle is stored in thread_id which will be returned to php userland. */
PHP_FUNCTION(fhread_create)
{
	char *gid;
	int gid_len;
	int status;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "s", &gid, &gid_len) == FAILURE) {
		RETURN_NULL();
	}

	// prepare fhread args
	FHREAD* fhread = malloc(sizeof(FHREAD));
	fhread->thread_id = malloc(sizeof(THREAD_T));

	// set global identifier for runnable zval
	fhread->gid = (char *) emalloc(gid_len + 1);
	fhread->gid_len = gid_len;
	memcpy(fhread->gid, gid, fhread->gid_len);

	// set current tsrmls
	fhread->c_tsrm_ls = TSRMLS_C;

	// init interpreter context for thread routine
	fhread->tsrm_ls = tsrm_new_interpreter_context();

	// create thread
	status = pthread_create(&fhread->thread_id, NULL, fhread_routine, fhread);

	RETURN_LONG((long)fhread->thread_id);
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

	// join thread with given id
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
	// Setup standard and fhreads object handlers
	fhreads_zend_handlers = zend_get_std_object_handlers();
	memcpy(&fhreads_handlers, fhreads_zend_handlers, sizeof(zend_object_handlers));

	g_tsrm_ls = ts_resource_ex(0, NULL);

	// override object handlers
	fhreads_handlers.write_property = fhread_write_property;
	fhreads_handlers.get = NULL;
	fhreads_handlers.set = NULL;
	fhreads_handlers.get_property_ptr_ptr = NULL;

#if PHP_VERSION_ID > 50399
	/* when the gc runs, it will fetch properties, every time */
	/* so we pass in a dummy function to control memory usage */
	/* properties copied will be destroyed with the object */
	fhreads_handlers.get_gc = NULL;
#endif

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
	PHP_FE(fhread_tls_get_id, NULL)
	PHP_FE(fhread_object_get_handle, NULL)
	PHP_FE(fhread_free_interpreter_context, NULL)
	PHP_FE(fhread_self, 	NULL)
	PHP_FE(fhread_create, 	NULL)
	PHP_FE(fhread_join, 	NULL)
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
