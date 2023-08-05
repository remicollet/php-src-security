/*
   +----------------------------------------------------------------------+
   | PHP Version 5                                                        |
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
   | Authors: Shane Caraveo <shane@php.net>                               |
   |          Wez Furlong <wez@thebrainroom.com>                          |
   +----------------------------------------------------------------------+
*/

/* $Id$ */

#ifndef PHP_LIBXML_H
#define PHP_LIBXML_H

#if HAVE_LIBXML
extern zend_module_entry libxml_module_entry;
#define libxml_module_ptr &libxml_module_entry

#ifdef PHP_WIN32
#	define PHP_LIBXML_API __declspec(dllexport)
#elif defined(__GNUC__) && __GNUC__ >= 4
#	define PHP_LIBXML_API __attribute__ ((visibility("default")))
#else
#	define PHP_LIBXML_API
#endif

#include "ext/standard/php_smart_str.h"
#include <libxml/tree.h>

#define LIBXML_SAVE_NOEMPTYTAG 1<<2

ZEND_BEGIN_MODULE_GLOBALS(libxml)
	zval *stream_context;
	smart_str error_buffer;
	zend_llist *error_list;
	struct _php_libxml_entity_resolver {
		zend_fcall_info			fci;
		zend_fcall_info_cache	fcc;
	} entity_loader;
	zend_bool entity_loader_disabled;
ZEND_END_MODULE_GLOBALS(libxml)

typedef struct _libxml_doc_props {
	int formatoutput;
	int validateonparse;
	int resolveexternals;
	int preservewhitespace;
	int substituteentities;
	int stricterror;
	int recover;
	HashTable *classmap;
} libxml_doc_props;

typedef struct _php_libxml_ref_obj {
	void *ptr;
	int   refcount;
	libxml_doc_props *doc_props;
} php_libxml_ref_obj;

typedef struct _php_libxml_node_ptr {
	xmlNodePtr node;
	int	refcount;
	void *_private;
} php_libxml_node_ptr;

typedef struct _php_libxml_node_object {
	zend_object  std;
	php_libxml_node_ptr *node;
	php_libxml_ref_obj *document;
	HashTable *properties;
} php_libxml_node_object;

typedef void * (*php_libxml_export_node) (zval *object TSRMLS_DC);

PHP_LIBXML_API int php_libxml_increment_node_ptr(php_libxml_node_object *object, xmlNodePtr node, void *private_data TSRMLS_DC);
PHP_LIBXML_API int php_libxml_decrement_node_ptr(php_libxml_node_object *object TSRMLS_DC);
PHP_LIBXML_API int php_libxml_increment_doc_ref(php_libxml_node_object *object, xmlDocPtr docp TSRMLS_DC);
PHP_LIBXML_API int php_libxml_decrement_doc_ref(php_libxml_node_object *object TSRMLS_DC);
PHP_LIBXML_API xmlNodePtr php_libxml_import_node(zval *object TSRMLS_DC);
PHP_LIBXML_API int php_libxml_register_export(zend_class_entry *ce, php_libxml_export_node export_function);
/* When an explicit freeing of node and children is required */
PHP_LIBXML_API void php_libxml_node_free_resource(xmlNodePtr node TSRMLS_DC);
/* When object dtor is called as node may still be referenced */
PHP_LIBXML_API void php_libxml_node_decrement_resource(php_libxml_node_object *object TSRMLS_DC);
PHP_LIBXML_API void php_libxml_error_handler(void *ctx, const char *msg, ...);
PHP_LIBXML_API void php_libxml_ctx_warning(void *ctx, const char *msg, ...);
PHP_LIBXML_API void php_libxml_ctx_error(void *ctx, const char *msg, ...);
PHP_LIBXML_API int php_libxml_xmlCheckUTF8(const unsigned char *s);
PHP_LIBXML_API zval *php_libxml_switch_context(zval *context TSRMLS_DC);
PHP_LIBXML_API void php_libxml_issue_error(int level, const char *msg TSRMLS_DC);
PHP_LIBXML_API zend_bool php_libxml_disable_entity_loader(zend_bool disable TSRMLS_DC);

/* Init/shutdown functions*/
PHP_LIBXML_API void php_libxml_initialize(void);
PHP_LIBXML_API void php_libxml_shutdown(void);

#ifdef ZTS
#define LIBXML(v) TSRMG(libxml_globals_id, zend_libxml_globals *, v)
#else
#define LIBXML(v) (libxml_globals.v)
#endif

/* Other extension may override the global state options, these global options
 * are copied initially to ctxt->options. Set the options to a known good value.
 * See libxml2 globals.c and parserInternals.c.
 * The unique_name argument allows multiple sanitizes and restores within the
 * same function, even nested is necessary. */
#define PHP_LIBXML_SANITIZE_GLOBALS_DECL(unique_name) \
	int xml_old_loadsubset_##unique_name; \
	int xml_old_validate_##unique_name; \
	int xml_old_pedantic_##unique_name; \
	int xml_old_substitute_##unique_name; \
	int xml_old_linenrs_##unique_name; \
	int xml_old_blanks_##unique_name;

#define PHP_LIBXML_SANITIZE_GLOBALS(unique_name) \
	xml_old_loadsubset_##unique_name = xmlLoadExtDtdDefaultValue; \
	xmlLoadExtDtdDefaultValue = 0; \
	xml_old_validate_##unique_name = xmlDoValidityCheckingDefaultValue; \
	xmlDoValidityCheckingDefaultValue = 0; \
	xml_old_pedantic_##unique_name = xmlPedanticParserDefault(0); \
	xml_old_substitute_##unique_name = xmlSubstituteEntitiesDefault(0); \
	xml_old_linenrs_##unique_name = xmlLineNumbersDefault(0); \
	xml_old_blanks_##unique_name = xmlKeepBlanksDefault(1);

#define PHP_LIBXML_RESTORE_GLOBALS(unique_name) \
	xmlLoadExtDtdDefaultValue = xml_old_loadsubset_##unique_name; \
	xmlDoValidityCheckingDefaultValue = xml_old_validate_##unique_name; \
	(void) xmlPedanticParserDefault(xml_old_pedantic_##unique_name); \
	(void) xmlSubstituteEntitiesDefault(xml_old_substitute_##unique_name); \
	(void) xmlLineNumbersDefault(xml_old_linenrs_##unique_name); \
	(void) xmlKeepBlanksDefault(xml_old_blanks_##unique_name);

/* Alternative for above, working directly on the context and not setting globals.
 * Generally faster because no locking is involved, and this has the advantage that it sets the options to a known good value. */
static zend_always_inline void php_libxml_sanitize_parse_ctxt_options(xmlParserCtxtPtr ctxt)
{
	ctxt->loadsubset = 0;
	ctxt->validate = 0;
	ctxt->pedantic = 0;
	ctxt->replaceEntities = 0;
	ctxt->linenumbers = 0;
	ctxt->keepBlanks = 1;
	ctxt->options = 0;
}

#else /* HAVE_LIBXML */
#define libxml_module_ptr NULL
#endif

#define phpext_libxml_ptr libxml_module_ptr

#endif /* PHP_LIBXML_H */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
