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

ZEND_DECLARE_MODULE_GLOBALS(fhreads)

ZEND_BEGIN_MODULE_GLOBALS(fhreads)
	HashTable global_value;
ZEND_END_MODULE_GLOBALS(fhreads)

/* True global resources - no need for thread safety here */
static zend_object_handlers fhreads_handlers, *fhreads_zend_handlers;
static fhread_objects_store fhread_objects;

ZEND_BEGIN_ARG_INFO_EX(arginfo_fhread_create, 0, 0, 1)
	ZEND_ARG_INFO(0, runnable)
	ZEND_ARG_INFO(1, thread_id)
ZEND_END_ARG_INFO()

/* {{{ PHP_INI */
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("fhreads.global_value", "42", PHP_INI_ALL, OnUpdateLong, global_value, zend_fhreads_globals, fhreads_globals)
PHP_INI_END() /* }}} */

/* {{{ Prepares zend objects store for threaded environment */
static void fhread_prepare_zend_objects_store()
{
	// recalc object store size that reallocation does not get trigged
	uint32_t new_objects_store_size = EG(objects_store).size * 1024 * 1024;
	// reallocate objects store at beginning
	if (EG(objects_store).size != new_objects_store_size) {
		EG(objects_store).size = new_objects_store_size;
		EG(objects_store).object_buckets = (zend_object **) erealloc(EG(objects_store).object_buckets, EG(objects_store).size * sizeof(zend_object*));
	}
} /* }}} */

/* {{{ Initialises the fhread object handlers */
static void fhread_init_object_handlers()
{
	// setup standard and fhreads object handlers
	fhreads_zend_handlers = zend_get_std_object_handlers();
	// copy handler for internal freaded objects usage
	memcpy(&fhreads_handlers, fhreads_zend_handlers, sizeof(zend_object_handlers));

	// override object handlers
	// fhreads_handlers.write_property = fhread_write_property;
	fhreads_handlers.get_gc = NULL;
} /* }}} */

/* {{{ Initialises the fhread object store */
void fhread_object_store_init()
{
	// init size
	fhread_objects.size = 1024 * 4;
	// init top
	fhread_objects.top = 1;
	// init free_list_head
	fhread_objects.free_list_head = -1;
	// init object store mutex
	pthread_mutex_init(&fhread_objects.mutex, NULL);
	// init object_buckets
	fhread_objects.object_buckets = (fhread_object **) emalloc(fhread_objects.size * sizeof(fhread_object*));
	// erase data
	memset(&fhread_objects.object_buckets[0], 0, sizeof(fhread_object*));
} /* }}} */

/* {{{ Creates and returns new fhread object */
void fhread_object_destroy(fhread_object* fhread)
{
	// realloc zend_constants hashtables for context to be destroyed savely
	FHREADS_EG(fhread->tsrm_ls, zend_constants) = (HashTable *) malloc(sizeof(HashTable));
	zend_hash_init_ex(FHREADS_EG(fhread->tsrm_ls, zend_constants), 1, NULL, NULL, 1, 0);
	// free context
	tsrm_free_interpreter_context(fhread->tsrm_ls);
	// destroy mutex
	pthread_mutex_destroy(&fhread->mutex);
	// finally free object itself
	efree(fhread);
} /* }}} */

/* {{{ Destroy the fhread object store */
void fhread_object_store_destroy()
{
	fhread_object **obj_ptr, **end, *obj;
	if (fhread_objects.top <= 1) {
		return;
	}
	// free all objects
	end = fhread_objects.object_buckets + 1;
	obj_ptr = fhread_objects.object_buckets + fhread_objects.top;
	do {
		obj_ptr--;
		obj = *obj_ptr;
		fhread_object_destroy(obj);
	} while (obj_ptr != end);
	// destroy object store mutex
	pthread_mutex_destroy(&fhread_objects.mutex);
	// free object buckets
	efree(fhread_objects.object_buckets);
	fhread_objects.object_buckets = NULL;
} /* }}} */

/* {{{ Adds a fhread object to store */
uint32_t fhread_object_store_put(fhread_object *object)
{
	// init vars
	int handle;
	// lock store access for thread safty inc stores top
	pthread_mutex_lock(&fhread_objects.mutex);
	handle = fhread_objects.top++;
	pthread_mutex_unlock(&fhread_objects.mutex);
	// add handle number to object itself
	object->handle = handle;
	// add object to store
	fhread_objects.object_buckets[handle] = object;
	// return handle
	return handle;
} /* }}} */

/* {{{ Creates and returns new fhread object */
static fhread_object* fhread_object_create()
{
	// prepare fhread
	fhread_object* fhread = emalloc(sizeof(fhread_object));
	// init executor flag
	fhread->is_initialized = 0;
	// set thread_id
	fhread->thread_id = tsrm_thread_id();
	// set creator tsrm ls
	fhread->c_tsrm_ls = tsrm_get_ls_cache();
	// init internal mutex
	pthread_mutex_init(&fhread->mutex, NULL);
	// return inited object
	return fhread;
} /* }}} */

/* {{{ Initialises the compile in fhreads context */
void fhread_init_compiler(fhread_object *fhread) /* {{{ */
{
	// call original compiler
	init_compiler();
} /* }}} */

/* {{{ Initialises the executor in fhreads context */
void fhread_init_executor(fhread_object *fhread) /* {{{ */
{
	// link included files
	EG(included_files) = FHREADS_EG(fhread->c_tsrm_ls, included_files);
	// link functions
	EG(function_table) = FHREADS_CG(fhread->c_tsrm_ls, function_table);
	// link classes
	EG(class_table) = FHREADS_CG(fhread->c_tsrm_ls, class_table);
	// link constants
	EG(zend_constants) = FHREADS_EG(fhread->c_tsrm_ls, zend_constants);
	// link regular list
	EG(regular_list) = FHREADS_EG(fhread->c_tsrm_ls, regular_list);
	// link regular list
	EG(persistent_list) = FHREADS_EG(fhread->c_tsrm_ls, persistent_list);
	// link objects_store
	EG(objects_store) = FHREADS_EG(fhread->c_tsrm_ls, objects_store);
	// link symbol table
	EG(symbol_table) = FHREADS_EG(fhread->c_tsrm_ls, symbol_table);

	zend_init_fpu();

	ZVAL_NULL(&EG(uninitialized_zval));
	ZVAL_NULL(&EG(error_zval));
	// destroys stack frame, therefore makes core dumps worthless
#if 0&&ZEND_DEBUG
	original_sigsegv_handler = signal(SIGSEGV, zend_handle_sigsegv);
#endif
	EG(symtable_cache_ptr) = EG(symtable_cache) - 1;
	EG(symtable_cache_limit) = EG(symtable_cache) + SYMTABLE_CACHE_SIZE - 1;
	EG(no_extensions) = 0;
	EG(in_autoload) = NULL;
	EG(autoload_func) = NULL;
	EG(error_handling) = EH_NORMAL;

	zend_vm_stack_init();

	EG(valid_symbol_table) = 1;
	EG(ticks_count) = 0;

	ZVAL_UNDEF(&EG(user_error_handler));

	EG(current_execute_data) = NULL;

	zend_stack_init(&EG(user_error_handlers_error_reporting), sizeof(int));
	zend_stack_init(&EG(user_error_handlers), sizeof(zval));
	zend_stack_init(&EG(user_exception_handlers), sizeof(zval));

	EG(full_tables_cleanup) = 0;
	#ifdef ZEND_WIN32
		EG(timed_out) = 0;
	#endif

	EG(scope) = NULL;
	EG(exception) = NULL;
	EG(prev_exception) = NULL;

	EG(ht_iterators_count) = sizeof(EG(ht_iterators_slots)) / sizeof(HashTableIterator);
	EG(ht_iterators_used) = 0;
	EG(ht_iterators) = EG(ht_iterators_slots);
	memset(EG(ht_iterators), 0, sizeof(EG(ht_iterators_slots)));

	EG(active) = 1;
} /* }}} */

/* {{{ Initialises the fhreads context */
void fhread_init(fhread_object* fhread)
{
	// check if initialization is needed
	if (fhread->is_initialized != 1) {
		// create new interpreter context
		void ***tsrm_ls = fhread->tsrm_ls = tsrm_new_interpreter_context();
		// set prepared thread ls
		tsrm_set_interpreter_context(tsrm_ls);
		// link server context
		SG(server_context) = FHREADS_SG(fhread->c_tsrm_ls, server_context);
		// init compiler
		fhread_init_compiler(fhread);
		// init executor
		fhread_init_executor(fhread);
		// set initialized flag to be true
		fhread->is_initialized = 1;
	}
	// unlock fhread mutex
	pthread_mutex_unlock(&fhread->mutex);
} /* }}} */

/* {{{ Run the runnable zval in given fhread context */
void fhread_run(fhread_object* fhread)
{
	zval runnable, ret_val;
	zend_object* obj;

	// init fhread before run it
	fhread_init(fhread);

	// get zval from object handle
	obj = EG(objects_store).object_buckets[fhread->runnable_handle];
	ZVAL_OBJ(&runnable, obj);

	// call run method
	// todo: check if interface runnable was implemented...
	zend_call_method_with_0_params(&runnable, obj->ce, NULL, "run", &ret_val);
} /* }}} */

/* {{{ The routine to call for pthread_create */
void *fhread_routine (void* ptr)
{
	// run fhread
	fhread_run(fhread_objects.object_buckets[*((uint32_t*)ptr)]);
	// exit thread
	pthread_exit(NULL);

#ifdef _WIN32
	return NULL; /* silence MSVC compiler */
#endif
} /* }}} */

/* {{{ proto fhread_tls_get_id()
	Obtain the identifier of currents ls context */
PHP_FUNCTION(fhread_tsrm_get_ls_cache)
{
	ZVAL_LONG(return_value, (long)tsrm_get_ls_cache());
} /* }}} */

/* {{{ proto fhread_mutex_init() */
PHP_FUNCTION(fhread_mutex_init)
{
	pthread_mutex_t *mutex;
	if ((mutex=(pthread_mutex_t*) calloc(1, sizeof(pthread_mutex_t)))!=NULL) {
		RETURN_LONG((ulong)mutex);
	}
} /* }}} */

/* {{{ proto fhread_mutex_lock() */
PHP_FUNCTION(fhread_mutex_lock)
{
	pthread_mutex_t *mutex;
	if (zend_parse_parameters(ZEND_NUM_ARGS(), "l", &mutex)==SUCCESS && mutex) {
		pthread_mutex_lock(mutex);
	}
} /* }}} */

/* {{{ proto fhread_mutex_unlock() */
PHP_FUNCTION(fhread_mutex_unlock)
{
	pthread_mutex_t *mutex;
	if (zend_parse_parameters(ZEND_NUM_ARGS(), "l", &mutex)==SUCCESS && mutex) {
		pthread_mutex_unlock(mutex);
	}
} /* }}} */

/* {{{ proto fhread_object_get_handle(object obj)
	Obtain the identifier of the given objects handle */
PHP_FUNCTION(fhread_object_get_handle)
{
	zval *obj;
	if (zend_parse_parameters(ZEND_NUM_ARGS(), "z", &obj) == FAILURE) {
		RETURN_NULL();
	}
	ZVAL_LONG(return_value, Z_OBJ_HANDLE_P(obj));
} /* }}} */

/* {{{ proto fhread_self()
	Obtain the identifier of the current thread. */
PHP_FUNCTION(fhread_self)
{
	ZVAL_LONG(return_value, tsrm_thread_id());
} /* }}} */

/* {{{ proto fhread_free_interpreter_context(long thread_id)
	frees an interpreter context created by a thread */
PHP_FUNCTION(fhread_free_interpreter_context)
{
	THREAD_T thread_id;
	int status;

	if (zend_parse_parameters(ZEND_NUM_ARGS(), "l", &thread_id) == FAILURE) {
		RETURN_NULL();
	}
} /* }}} */

/* {{{ proto fhread_create()
   Create a new thread, starting with execution of START-ROUTINE
   getting passed ARG.  Creation attributed come from ATTR.  The new
   handle is stored in thread_id which will be returned to php userland. */
PHP_FUNCTION(fhread_create)
{
	// init vars
	zval *runnable = NULL, *thread_id = NULL;

	// parse params
	if (zend_parse_parameters(ZEND_NUM_ARGS(), "o|z/", &runnable, &thread_id) == FAILURE)
		return;

	// todo: check if free fhread object is available an reuse it...

	// create new fhread object
	fhread_object* fhread = fhread_object_create();
	// add new object and save handle
	uint32_t fhreads_object_handle = fhread_object_store_put(fhread);
	// save runnable handle from zval
	fhread->runnable_handle = Z_OBJ_HANDLE_P(runnable);

	// lock fhread mutex to wait for everything being ready
	pthread_mutex_lock(&fhread->mutex);

	// create thread and start fhread__routine
	int pthread_status = pthread_create(&fhread->thread_id, NULL, fhread_routine, &fhreads_object_handle);

	// check if second param was given for thread id reference
	if (thread_id) {
		zval_dtor(thread_id);
		ZVAL_LONG(thread_id, (long)fhread->thread_id);
	}

	pthread_mutex_lock(&fhread->mutex);
	pthread_mutex_unlock(&fhread->mutex);

	// return thread status
	RETURN_LONG((long)pthread_status);
} /* }}} */

/* {{{ proto fhread_join()
   Make calling thread wait for termination of the given thread id.  The
   exit status of the thread is stored in *THREAD_RETURN, if THREAD_RETURN
   is not NULL.*/
PHP_FUNCTION(fhread_join)
{
	long thread_id;
	int status;

	// parse thread id for thread to join to
	if (zend_parse_parameters(ZEND_NUM_ARGS(), "l", &thread_id) == FAILURE) {
		RETURN_NULL();
	}

	// join thread with given id
	status = pthread_join((pthread_t)thread_id, NULL);

	// return status
	RETURN_LONG((long)status);
} /* }}} */

/* {{{ PHP_MINIT_FUNCTION */
PHP_MINIT_FUNCTION(fhreads)
{
	// init module globals
	ZEND_INIT_MODULE_GLOBALS(fhreads, NULL, NULL);

	// register ini entries
	REGISTER_INI_ENTRIES();

	// init fhread object handlers
	fhread_init_object_handlers();

	return SUCCESS;
} /* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION */
PHP_MSHUTDOWN_FUNCTION(fhreads)
{
	UNREGISTER_INI_ENTRIES();

	return SUCCESS;
} /* }}} */

/* Remove if there's nothing to do at request start */
/* {{{ PHP_RINIT_FUNCTION */
PHP_RINIT_FUNCTION(fhreads)
{
	// init fhread objects store
	fhread_object_store_init();

	// prepare zend executor globals objects store
	fhread_prepare_zend_objects_store();

	return SUCCESS;
} /* }}} */

/* Remove if there's nothing to do at request end */
/* {{{ PHP_RSHUTDOWN_FUNCTION */
PHP_RSHUTDOWN_FUNCTION(fhreads)
{
	// destroy fhreads object store
	fhread_object_store_destroy();

	return SUCCESS;
} /* }}} */

/* {{{ PHP_MINFO_FUNCTION */
PHP_MINFO_FUNCTION(fhreads)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "fhreads support", "enabled");
	php_info_print_table_row(2, "fhreads version", PHP_FHREADS_VERSION);
	php_info_print_table_end();

	DISPLAY_INI_ENTRIES();
} /* }}} */

/* {{{ fhreads_functions[]
 *
 * Every user visible function must have an entry in fhreads_functions[]. */
const zend_function_entry fhreads_functions[] = {
	PHP_FE(fhread_tsrm_get_ls_cache,		NULL)
	PHP_FE(fhread_object_get_handle, 		NULL)
	PHP_FE(fhread_free_interpreter_context, NULL)
	PHP_FE(fhread_self, 					NULL)
	PHP_FE(fhread_create, 					arginfo_fhread_create)
	PHP_FE(fhread_join, 					NULL)
	PHP_FE(fhread_mutex_init, 				NULL)
	PHP_FE(fhread_mutex_lock, 				NULL)
	PHP_FE(fhread_mutex_unlock,				NULL)
	PHP_FE_END	/* Must be the last line in fhreads_functions[] */
}; /* }}} */

/* {{{ fhreads_module_entry */
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
}; /* }}} */

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
