/*
  +----------------------------------------------------------------------+
  | PHP Version 7                                                        |
  +----------------------------------------------------------------------+
  | Copyright (c) 1997-2016 The PHP Group                                |
  +----------------------------------------------------------------------+
  | This source file is subject to version 3.01 of the PHP license,      |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.php.net/license/3_01.txt                                  |
  | If you did not receive a copy of the PHP license and are unable to   |
  | obtain it through the world-wide-web, please send a note to          |
  | license@php.net so we can mail you a copy immediately.               |
  +----------------------------------------------------------------------+
  | Author:                                                              |
  +----------------------------------------------------------------------+
*/

/* $Id$ */

#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include "php.h"
#include "php_ini.h"
#include "ext/standard/info.h"
#include "php_rockets.h"

#include <netinet/in.h>

/* If you declare any globals in php_rockets.h uncomment this:
ZEND_DECLARE_MODULE_GLOBALS(rockets)
*/

/* True global resources - no need for thread safety here */
static int le_rockets;

/* {{{ PHP_INI
 */
/* Remove comments and fill if you need to have entries in php.ini
PHP_INI_BEGIN()
    STD_PHP_INI_ENTRY("rockets.global_value",      "42", PHP_INI_ALL, OnUpdateLong, global_value, zend_rockets_globals, rockets_globals)
    STD_PHP_INI_ENTRY("rockets.global_string", "foobar", PHP_INI_ALL, OnUpdateString, global_string, zend_rockets_globals, rockets_globals)
PHP_INI_END()
*/
/* }}} */

/* {{{ proto int rockets_socket()
		Create a new socket of type TYPE in domain DOMAIN, using
		protocol PROTOCOL. If PROTOCOL is zero, one is chosen automatically.
		Returns a file descriptor for the new socket, or -1 for errors.  */
PHP_FUNCTION(rockets_socket)
{
	zend_long arg1, arg2, arg3 = 0;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ll|l", &arg1, &arg2, &arg3) == FAILURE) {
		return;
	}

	RETURN_LONG((zend_long)socket((int)arg1, (int)arg2, (int)arg3));
}

/* {{{ proto int rockets_bind()
		Give the socket FD the local address ADDR (which is LEN bytes long).  */
PHP_FUNCTION(rockets_bind)
{
	char ip[INET_ADDRSTRLEN];
	zend_long fd, ip_len, port, family = AF_INET;
	struct sockaddr_in sin = { 0 };

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "lsl|l", &fd, &ip, &ip_len, &port, &family) == FAILURE) {
		return;
	}

	sin.sin_family = family;
	sin.sin_port = htons(port);
	inet_pton(AF_INET, ip, &(sin.sin_addr));

	RETURN_LONG((zend_long)bind(fd, (struct sockaddr*)&sin, sizeof(sin)));
}

/* {{{ proto int rockets_listen()
		Prepare to accept connections on socket FD.
		N connection requests will be queued before further requests are refused.
		Returns 0 on success, -1 for errors.  */
PHP_FUNCTION(rockets_listen)
{
	zend_long arg1, arg2 = 0;
	int backlog = 128;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l|l", &arg1, &arg2) == FAILURE) {
		return;
	}
	if (arg2 > 0) {
		backlog = (int)arg2;
	}

	RETURN_LONG((zend_long)listen((int)arg1, backlog));
}

/* {{{ proto int rockets_accept()
		Await a connection on socket FD.
		When a connection arrives, open a new socket to communicate with it,
		set *ADDR (which is *ADDR_LEN bytes long) to the address of the connecting
		peer and *ADDR_LEN to the address's actual length, and return the
		new socket's descriptor, or -1 for errors. */
PHP_FUNCTION(rockets_accept)
{
	zend_long arg1;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &arg1) == FAILURE) {
		return;
	}

	RETURN_LONG((zend_long)accept((int)arg1, (struct sockaddr*)NULL, NULL));
}

/* {{{ proto boolean rockets_close()
		Close the file descriptor FD.
		This function is a cancellation point and therefore not marked with */
PHP_FUNCTION(rockets_close)
{
	zend_long arg1;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l", &arg1) == FAILURE) {
		return;
	}

	RETURN_LONG((zend_long)close((int)arg1));
}

/* {{{ proto boolean rockets_setsockopt()
		Set socket FD's option OPTNAME at protocol level LEVEL
		to *OPTVAL (which is OPTLEN bytes long).
		Returns 0 on success, -1 for errors. }}} */
PHP_FUNCTION(rockets_setsockopt)
{
	zend_long arg1, arg2, arg3, arg4;
	size_t optVal;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "llll", &arg1, &arg2, &arg3, &arg4) == FAILURE) {
		return;
	}
	optVal = (int)optVal;

	RETURN_LONG((zend_long)setsockopt((int)arg1, (int)arg2, (int)arg3, &optVal, sizeof(optVal)));
}

/* {{{ proto boolean rockets_getsockopt()
		Put the current value for socket FD's option OPTNAME at protocol level LEVEL
		into OPTVAL (which is *OPTLEN bytes long), and set *OPTLEN to the value's
		actual length.  Returns 0 on success, -1 for errors. }}} */
PHP_FUNCTION(rockets_getsockopt)
{
	void* opt_val;
	int opt_val_len;
	zend_long arg1, arg2, arg3;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "lll", &arg1, &arg2, &arg3) == FAILURE) {
		return;
	}

	getsockopt((int)arg1, (int)arg2, (int)arg3, &opt_val, &opt_val_len);

	RETURN_LONG((zend_long)opt_val);
}

/* {{{ proto boolean rockets_recv()
		Read N bytes into BUF from socket FD.
		Returns the number read or -1 for errors. }}} */
PHP_FUNCTION(rockets_recv)
{
	size_t byte_count, flags = 0, recv_len = 128;
	zend_long arg1, arg2 = 128, arg3 = 0;
	char buf[8192];

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "l|ll", &arg1, &arg2, &arg3) == FAILURE) {
		return;
	}

	bzero(buf, sizeof(buf));
	byte_count = recv((int)arg1, buf, (int)arg2, (int)arg3);

	RETURN_STRINGL(buf, byte_count);
}

/* {{{ proto boolean rockets_send()
		Send N bytes of BUF to socket FD.  Returns the number sent or -1. }}}	*/
PHP_FUNCTION(rockets_send)
{
	size_t byte_count, flags = 0, arg2_len;
	zend_long arg1, arg3 = 128;
	char *arg2;

	if (zend_parse_parameters(ZEND_NUM_ARGS() TSRMLS_CC, "ls|l", &arg1, &arg2, &arg2_len, &arg3) == FAILURE) {
		return;
	}

	RETURN_LONG((zend_long)send((int)arg1, arg2, arg2_len, (int)arg3));
}


PHP_FUNCTION(rockets_SSL_new)
{

}

PHP_FUNCTION(rockets_SSL_set_fd)
{

}

PHP_FUNCTION(rockets_SSL_CTX_new)
{

}

PHP_FUNCTION(rockets_SSL_CTX_set_options)
{

}

PHP_FUNCTION(rockets_SSL_CTX_use_certificate_file)
{

}

PHP_FUNCTION(rockets_SSL_CTX_use_PrivateKey_file)
{

}

/* {{{ php_rockets_init_globals
 */
/* Uncomment this function if you have INI entries
static void php_rockets_init_globals(zend_rockets_globals *rockets_globals)
{
	rockets_globals->global_value = 0;
	rockets_globals->global_string = NULL;
}
*/
/* }}} */

/* {{{ PHP_MINIT_FUNCTION
 */
PHP_MINIT_FUNCTION(rockets)
{
	/* If you have INI entries, uncomment these lines
	REGISTER_INI_ENTRIES();
	*/
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MSHUTDOWN_FUNCTION
 */
PHP_MSHUTDOWN_FUNCTION(rockets)
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
PHP_RINIT_FUNCTION(rockets)
{
#if defined(COMPILE_DL_ROCKETS) && defined(ZTS)
	ZEND_TSRMLS_CACHE_UPDATE();
#endif
	return SUCCESS;
}
/* }}} */

/* Remove if there's nothing to do at request end */
/* {{{ PHP_RSHUTDOWN_FUNCTION
 */
PHP_RSHUTDOWN_FUNCTION(rockets)
{
	return SUCCESS;
}
/* }}} */

/* {{{ PHP_MINFO_FUNCTION
 */
PHP_MINFO_FUNCTION(rockets)
{
	php_info_print_table_start();
	php_info_print_table_header(2, "rockets support", "enabled");
	php_info_print_table_end();

	/* Remove comments if you have entries in php.ini
	DISPLAY_INI_ENTRIES();
	*/
}
/* }}} */

/* {{{ rockets_functions[]
 *
 * Every user visible function must have an entry in rockets_functions[].
 */
const zend_function_entry rockets_functions[] = {
	PHP_FE(rockets_socket, NULL)
	PHP_FE(rockets_bind, NULL)
	PHP_FE(rockets_listen, NULL)
	PHP_FE(rockets_accept, NULL)
	PHP_FE(rockets_close, NULL)
	PHP_FE(rockets_setsockopt, NULL)
	PHP_FE(rockets_getsockopt, NULL)
	PHP_FE(rockets_recv, NULL)
	PHP_FE(rockets_send, NULL)
	PHP_FE(rockets_SSL_new, NULL)
	PHP_FE(rockets_SSL_set_fd, NULL)
	PHP_FE(rockets_SSL_CTX_new, NULL)
	PHP_FE(rockets_SSL_CTX_set_options, NULL)
	PHP_FE(rockets_SSL_CTX_use_certificate_file, NULL)
	PHP_FE(rockets_SSL_CTX_use_PrivateKey_file, NULL)
	PHP_FE_END
};
/* }}} */

/* {{{ rockets_module_entry
 */
zend_module_entry rockets_module_entry = {
	STANDARD_MODULE_HEADER,
	"rockets",
	rockets_functions,
	PHP_MINIT(rockets),
	PHP_MSHUTDOWN(rockets),
	PHP_RINIT(rockets),		/* Replace with NULL if there's nothing to do at request start */
	PHP_RSHUTDOWN(rockets),	/* Replace with NULL if there's nothing to do at request end */
	PHP_MINFO(rockets),
	PHP_ROCKETS_VERSION,
	STANDARD_MODULE_PROPERTIES
};
/* }}} */

#ifdef COMPILE_DL_ROCKETS
#ifdef ZTS
ZEND_TSRMLS_CACHE_DEFINE()
#endif
ZEND_GET_MODULE(rockets)
#endif

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
