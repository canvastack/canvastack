# Security Penetration Testing - Attack Scenarios Documentation

## Overview

This document provides comprehensive documentation of all security attack scenarios tested in the Form Components Audit & Fixes project. Each scenario simulates real-world attacks that malicious users might attempt against the form components system.

**Test File:** `tests/Security/PenetrationTest.php`

**Coverage Areas:**
1. XSS (Cross-Site Scripting) attacks
2. SQL Injection attacks
3. Path Traversal attacks
4. Attribute Injection attacks
5. Encrypted Data Tampering attacks
6. Additional security concerns (CSRF, Mass Assignment, File Upload, DoS)

---

## 1. XSS (Cross-Site Scripting) Attack Scenarios

### Attack Scenario 1: Basic XSS via Script Tag Injection
**Test Method:** `test_xss_script_tag_injection_is_blocked()`

**Attack Vector:**
```html
<script>alert("XSS")</script>
```

**Target:** Text input fields

**How Attack Works:**
- Attacker enters JavaScript code wrapped in `<script>` tags into form fields
- If not escaped, browser executes the JavaScript when page renders
- Can steal cookies, session tokens, or perform actions as the user

**Expected Defense:**
- All `<script>` tags should be HTML-escaped to `&lt;script&gt;`
- JavaScript code becomes plain text and won't execute
- Uses `canvastack_form_escape_html()` function

**Validates:** Requirements 1.1, 1.8 (XSS Protection)

---

### Attack Scenario 2: XSS via Event Handler Attributes
**Test Method:** `test_xss_event_handler_injection_is_blocked()`

**Attack Vectors:**
```html
onclick="alert(1)"
onerror="alert(1)"
onload="alert(1)"
onmouseover="alert(1)"
onfocus="alert(1)"
```

**Target:** HTML attributes in form elements

**How Attack Works:**
- Attacker injects event handlers that execute JavaScript on user interaction
- Example: `<input onclick="alert(1)">` executes when user clicks
- More subtle than script tags, often bypasses basic filters

**Expected Defense:**
- Dangerous event handlers blocked by `canvastack_form_validate_attributes()`
- Attributes array validated before rendering
- Event handlers either removed or escaped

**Validates:** Requirements 2.4 (Dangerous Attribute Blocking)

---

### Attack Scenario 3: XSS via Checkbox Label Injection
**Test Method:** `test_xss_checkbox_label_injection_is_blocked()`

**Attack Vector:**
```html
<img src=x onerror="alert(1)">
```

**Target:** Checkbox label parameter in `drawCheckBox()`

**How Attack Works:**
- Attacker provides malicious HTML as checkbox label
- Image tag with invalid src triggers onerror event
- JavaScript executes when checkbox is rendered

**Expected Defense:**
- Label parameter escaped in `drawCheckBox()` method
- HTML tags converted to entities
- Output marked with SafeHtml to prevent double-encoding

**Validates:** Requirements 1.2 (Label Escaping)

---

### Attack Scenario 4: XSS via Radio Button Label Injection
**Test Method:** `test_xss_radio_label_injection_is_blocked()`

**Attack Vector:**
```html
<svg onload="alert(1)">
```

**Target:** Radio button label parameter in `drawRadioBox()`

**How Attack Works:**
- SVG tags can contain JavaScript in onload events
- Executes immediately when SVG is rendered
- Often bypasses filters that only check for script tags

**Expected Defense:**
- Label parameter escaped in `drawRadioBox()` method
- SVG tags and event handlers converted to entities
- SafeHtml marker prevents double-encoding

**Validates:** Requirements 1.3 (Label Escaping)

---

### Attack Scenario 5: XSS via Tab Label Injection
**Test Method:** `test_xss_tab_label_injection_is_blocked()`

**Attack Vector:**
```html
<iframe src="javascript:alert(1)"></iframe>
```

**Target:** Tab navigation labels in `renderTab()`

**How Attack Works:**
- Iframe with javascript: protocol executes code
- Can load external malicious content
- Particularly dangerous as iframes can bypass same-origin policy

**Expected Defense:**
- Tab labels escaped in `renderTab()` method
- Iframe tags and javascript: protocol blocked
- Tab marker validation ensures proper structure

**Validates:** Requirements 1.4 (Tab Label Escaping)

---

### Attack Scenario 6: XSS via Placeholder Injection
**Test Method:** `test_xss_placeholder_injection_is_blocked()`

**Attack Vector:**
```html
" onfocus="alert(1)" data-x="
```

**Target:** Placeholder attributes in text inputs

**How Attack Works:**
- Attacker breaks out of placeholder attribute with quote
- Injects new attributes including event handlers
- Executes when user focuses on the input field

**Expected Defense:**
- Placeholder values escaped before rendering
- Quotes converted to entities preventing attribute breakout
- Attribute validation blocks dangerous handlers

**Validates:** Requirements 1.6 (Attribute Value Escaping)

---

### Attack Scenario 7: XSS via Select Option Injection
**Test Method:** `test_xss_select_option_injection_is_blocked()`

**Attack Vector:**
```html
<script>alert(1)</script>
```

**Target:** Select dropdown option labels and values

**How Attack Works:**
- Attacker provides malicious HTML as option label
- Script executes when dropdown is rendered
- Can affect all users viewing the form

**Expected Defense:**
- Option labels and values escaped in `selectbox()` method
- Script tags converted to entities
- SafeHtml marker applied to output

**Validates:** Requirements 1.1, 1.8 (XSS Protection)

---

### Attack Scenario 8: XSS via Filename Display Injection
**Test Method:** `test_xss_filename_display_injection_is_blocked()`

**Attack Vector:**
```html
<script>alert(1)</script>.jpg
```

**Target:** File input display showing uploaded filename

**How Attack Works:**
- Attacker uploads file with malicious name
- Filename displayed to user without escaping
- Script executes when filename is shown

**Expected Defense:**
- Filename escaped in `inputFile()` method before display
- Script tags converted to entities
- File validation also checks for dangerous characters

**Validates:** Requirements 1.5 (File Value Escaping)

---

### Attack Scenario 9: XSS via Dynamic Class Injection
**Test Method:** `test_xss_dynamic_class_injection_is_blocked()`

**Attack Vector:**
```html
" onclick="alert(1)" class="
```

**Target:** CSS class names generated from user input

**How Attack Works:**
- Attacker breaks out of class attribute with quote
- Injects event handler as new attribute
- Executes on user interaction

**Expected Defense:**
- Class names escaped before rendering
- Quotes converted to entities
- Attribute validation blocks event handlers

**Validates:** Requirements 1.7 (Dynamic Class/ID Escaping)

---

### Attack Scenario 10: XSS via Textarea Content Injection
**Test Method:** `test_xss_textarea_content_injection_is_blocked()`

**Attack Vector:**
```html
</textarea><script>alert(1)</script><textarea>
```

**Target:** Textarea default value

**How Attack Works:**
- Attacker closes textarea tag prematurely
- Injects script tag outside textarea
- Opens new textarea to hide remaining content

**Expected Defense:**
- Textarea content escaped before rendering
- Closing tags converted to entities
- Cannot break out of textarea context

**Validates:** Requirements 1.1, 1.8 (XSS Protection)

---

## 2. SQL Injection Attack Scenarios

### Attack Scenario 11: SQL Injection via Sync() Encrypted Query
**Test Method:** `test_sql_injection_in_sync_query_is_blocked()`

**Attack Vectors:**
```sql
'; DROP TABLE users; --
' OR '1'='1
' UNION SELECT * FROM users --
'; DELETE FROM users WHERE '1'='1
' AND 1=1 --
```

**Target:** `sync()` method's encrypted query parameter

**How Attack Works:**
- Attacker injects SQL commands into query parameter
- If query is not validated before encryption, malicious SQL is encrypted
- When decrypted and executed, can drop tables, extract data, or modify records

**Expected Defense:**
- Query validated for dangerous SQL patterns before encryption
- Keywords like DROP, DELETE, UNION blocked
- Exception thrown for malicious queries
- Security logging records attempt

**Validates:** Requirements 20.1 (SQL Injection Prevention in Sync)

---

### Attack Scenario 12: SQL Injection via Sync() Field Names
**Test Method:** `test_sql_injection_in_sync_field_names_is_blocked()`

**Attack Vector:**
```sql
field'; DROP TABLE users; --
```

**Target:** Source and target field names in `sync()` method

**How Attack Works:**
- Attacker injects SQL through field name parameters
- Field names often used directly in queries
- Can execute arbitrary SQL if not validated

**Expected Defense:**
- Field names validated against allowed patterns
- Only alphanumeric and underscore characters allowed
- InvalidArgumentException thrown for invalid names

**Validates:** Requirements 20.3 (Sync Field Name Validation)

---

## 3. Path Traversal Attack Scenarios

### Attack Scenario 13: Path Traversal via Upload Path
**Test Method:** `test_path_traversal_in_upload_path_is_blocked()`

**Attack Vectors:**
```
../../../etc/passwd
..\\..\\..\\windows\\system32
uploads/../../config/database.php
./../../.env
uploads/../../../secret.txt
```

**Target:** File upload path in File trait

**How Attack Works:**
- Attacker uses `../` sequences to navigate up directory tree
- Can access files outside allowed upload directory
- Can read sensitive files like .env, config files, or system files

**Expected Defense:**
- Path validated by `canvastack_form_validate_path()`
- `../` and `..\` sequences detected and blocked
- realpath() used to resolve symbolic links
- SecurityException thrown for traversal attempts

**Validates:** Requirements 2.3, 10.1 (Path Traversal Prevention)

---

### Attack Scenario 14: Path Traversal via Asset Path
**Test Method:** `test_path_traversal_in_asset_path_is_blocked()`

**Attack Vector:**
```
assets/../../config/app.php
```

**Target:** Asset path in `setAssetPath()` method

**How Attack Works:**
- Attacker tries to access configuration files via asset path
- Can expose sensitive configuration data
- May allow reading of source code

**Expected Defense:**
- Asset path validated against base directory
- Path must remain within allowed assets folder
- Exception thrown for traversal attempts

**Validates:** Requirements 10.2 (Asset Path Validation)

---

### Attack Scenario 15: Path Traversal via Thumbnail Path
**Test Method:** `test_path_traversal_in_thumbnail_path_is_blocked()`

**Attack Vector:**
```
thumb/../../uploads/malicious.php
```

**Target:** Thumbnail path in thumbnail generation

**How Attack Works:**
- Attacker tries to create thumbnail in unauthorized location
- Can overwrite existing files
- Can place malicious files in web-accessible directories

**Expected Defense:**
- Thumbnail path validated against base directory
- Must remain within thumb folder
- Exception thrown for traversal attempts

**Validates:** Requirements 10.3 (Thumbnail Path Validation)

---

### Attack Scenario 16: Null Byte Injection in File Path
**Test Method:** `test_null_byte_injection_in_path_is_blocked()`

**Attack Vector:**
```
uploads/file.php\0.jpg
```

**Target:** File paths in upload operations

**How Attack Works:**
- Null byte (\0) can truncate strings in some languages
- File saved as file.php but validation sees file.php\0.jpg
- Bypasses extension checks
- PHP file can be executed on server

**Expected Defense:**
- Null bytes detected in path validation
- InvalidArgumentException thrown
- Path sanitization removes null bytes

**Validates:** Requirements 2.3 (Path Validation)

---

## 4. Attribute Injection Attack Scenarios

### Attack Scenario 17: Dangerous Event Handler Injection
**Test Method:** `test_dangerous_event_handlers_are_blocked()`

**Attack Vectors:**
```javascript
onclick="alert(1)"
onerror="alert(1)"
onload="alert(1)"
onmouseover="alert(1)"
onfocus="alert(1)"
onblur="alert(1)"
onchange="alert(1)"
onsubmit="alert(1)"
```

**Target:** Attributes array in all form elements

**How Attack Works:**
- Attacker passes event handlers in attributes array
- Event handlers execute JavaScript on user interaction
- Can steal data, perform actions, or redirect users

**Expected Defense:**
- `canvastack_form_validate_attributes()` blocks event handlers
- Whitelist approach: only safe attributes allowed
- InvalidArgumentException thrown for dangerous attributes

**Validates:** Requirements 2.4 (Dangerous Attribute Blocking)

---

### Attack Scenario 18: JavaScript Protocol Injection
**Test Method:** `test_javascript_protocol_injection_is_blocked()`

**Attack Vectors:**
```javascript
href="javascript:alert(1)"
src="javascript:void(0)"
data-url="javascript:malicious()"
```

**Target:** URL attributes (href, src, data-url)

**How Attack Works:**
- javascript: protocol executes code when link clicked or resource loaded
- Can bypass some XSS filters
- Particularly dangerous in href and src attributes

**Expected Defense:**
- javascript: protocol detected and blocked
- URL validation checks for dangerous protocols
- InvalidArgumentException thrown

**Validates:** Requirements 2.4 (Dangerous Attribute Blocking)

---

### Attack Scenario 19: Data URI Script Injection
**Test Method:** `test_data_uri_script_injection_is_blocked()`

**Attack Vectors:**
```
data:text/html,<script>alert(1)</script>
data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==
```

**Target:** src and href attributes

**How Attack Works:**
- data: URIs can embed HTML/JavaScript directly
- Base64 encoding can hide malicious content
- Executes when resource is loaded

**Expected Defense:**
- Dangerous data URIs detected and blocked
- Content type validation for data URIs
- InvalidArgumentException thrown

**Validates:** Requirements 2.4 (Dangerous Attribute Blocking)

---

### Attack Scenario 20: Style Attribute Injection
**Test Method:** `test_malicious_style_injection_is_blocked()`

**Attack Vectors:**
```css
style="expression(alert(1))"
style="behavior:url(xss.htc)"
style="background:url(javascript:alert(1))"
```

**Target:** style attribute in form elements

**How Attack Works:**
- CSS expressions can execute JavaScript (IE)
- behavior property can load external scripts
- url() with javascript: protocol executes code

**Expected Defense:**
- Dangerous CSS patterns detected
- expression(), behavior, javascript: in styles blocked
- InvalidArgumentException thrown

**Validates:** Requirements 2.4 (Dangerous Attribute Blocking)

---

## 5. Encrypted Data Tampering Attack Scenarios

### Attack Scenario 21: Model Name Tampering
**Test Method:** `test_model_name_tampering_is_detected()`

**Attack Vector:**
- Intercept encrypted model name
- Modify encrypted data
- Submit tampered data

**Target:** `model()` method's encrypted model name

**How Attack Works:**
- Attacker intercepts encrypted model name from form
- Modifies encrypted data to access different model
- Attempts to access unauthorized data

**Expected Defense:**
- Integrity checking detects tampering
- HMAC or signature validates data hasn't changed
- Exception thrown for tampered data

**Validates:** Requirements 19.2 (Model Encryption Integrity)

---

### Attack Scenario 22: Sync Query Tampering
**Test Method:** `test_sync_query_tampering_is_detected()`

**Attack Vector:**
- Intercept encrypted sync query
- Modify encrypted data
- Submit tampered query

**Target:** `sync()` method's encrypted query

**How Attack Works:**
- Attacker intercepts encrypted query from AJAX request
- Modifies encrypted data to execute different SQL
- Attempts to extract or modify unauthorized data

**Expected Defense:**
- Integrity checking detects tampering
- Decryption fails for tampered data
- Exception thrown with integrity error

**Validates:** Requirements 20.2 (Sync Data Integrity)

---

### Attack Scenario 23: Replay Attack with Old Encrypted Data
**Test Method:** `test_encrypted_data_replay_attack_is_detected()`

**Attack Vector:**
- Capture encrypted data from one context
- Reuse in different context
- Attempt to access unauthorized resources

**Target:** Encrypted model names and queries

**How Attack Works:**
- Attacker captures valid encrypted data
- Replays it in different context or time
- Attempts to bypass authorization checks

**Expected Defense:**
- Context validation ensures data used in correct context
- Timestamp validation prevents old data reuse
- Exception thrown for context mismatch

**Validates:** Requirements 19.1, 19.2 (Secure Model Encryption)

---

### Attack Scenario 24: Encryption Key Manipulation
**Test Method:** `test_decryption_with_wrong_key_fails()`

**Attack Vector:**
- Attempt to decrypt with wrong key
- Provide fake encrypted data
- Try to bypass encryption

**Target:** Laravel's encryption system

**How Attack Works:**
- Attacker tries to decrypt data without proper key
- Attempts to forge encrypted data
- Tries to bypass encryption entirely

**Expected Defense:**
- Decryption fails with wrong key
- MAC validation detects forged data
- Exception thrown for invalid encrypted data

**Validates:** Requirements 19.1 (Secure Encryption)

---

## 6. Additional Security Attack Scenarios

### Attack Scenario 25: CSRF Token Bypass Attempt
**Test Method:** `test_csrf_token_is_required()`

**Attack Vector:**
- Submit form without CSRF token
- Use expired CSRF token
- Use token from different session

**Target:** Form submission

**How Attack Works:**
- Attacker tricks user into submitting form from malicious site
- Without CSRF protection, form would be processed
- Can perform unauthorized actions as the user

**Expected Defense:**
- CSRF token automatically included in forms
- Laravel validates token on submission
- Request rejected without valid token

**Validates:** Laravel's built-in CSRF protection

---

### Attack Scenario 26: Mass Assignment Vulnerability
**Test Method:** `test_hidden_model_attributes_are_protected()`

**Attack Vector:**
- Submit form with extra fields
- Attempt to set protected attributes
- Try to modify hidden fields

**Target:** Model binding in `model()` method

**How Attack Works:**
- Attacker adds extra fields to form submission
- Attempts to set protected attributes like password, role, is_admin
- Can escalate privileges or modify sensitive data

**Expected Defense:**
- Model's hidden/guarded attributes respected
- Protected fields not exposed in form
- Mass assignment protection enforced

**Validates:** Requirements 19.4, 19.5 (Mass Assignment Prevention)

---

### Attack Scenario 27: Executable File Upload
**Test Method:** `test_executable_file_upload_is_blocked()`

**Attack Vectors:**
```
malware.php
virus.exe
script.sh
backdoor.bat
```

**Target:** File upload functionality

**How Attack Works:**
- Attacker uploads executable file
- If saved in web-accessible directory, can be executed
- Can compromise entire server

**Expected Defense:**
- Extension whitelist blocks executables
- Only safe extensions allowed (jpg, png, pdf, etc.)
- InvalidArgumentException thrown for dangerous extensions

**Validates:** Requirements 9.1 (File Extension Validation)

---

### Attack Scenario 28: MIME Type Mismatch Attack
**Test Method:** `test_mime_type_mismatch_is_detected()`

**Attack Vector:**
- Upload PHP file with .jpg extension
- Set MIME type to image/jpeg
- Bypass extension-only validation

**Target:** File upload MIME type validation

**How Attack Works:**
- Attacker disguises malicious file as image
- Extension and MIME type claim it's an image
- Actual content is executable code

**Expected Defense:**
- Actual file content validated (magic bytes)
- MIME type checked against file content
- Mismatch detected and rejected

**Validates:** Requirements 9.2 (MIME Type Content Validation)

---

### Attack Scenario 29: Polyglot File Upload
**Test Method:** `test_polyglot_file_upload_is_detected()`

**Attack Vector:**
- File that is both valid image and valid PHP
- Example: GIF header + PHP code
- Passes image validation but executes as PHP

**Target:** File upload content validation

**How Attack Works:**
- Polyglot files are valid in multiple formats
- Passes image validation checks
- Can be executed as PHP if accessed directly

**Expected Defense:**
- Advanced content scanning required
- Consider virus scanning integration
- File permissions prevent execution (0644)

**Status:** Test marked incomplete - requires advanced scanning

**Validates:** Requirements 9.2, 9.6 (Content Validation, Permissions)

---

### Attack Scenario 30: Billion Laughs Attack (XML Bomb)
**Test Method:** `test_xml_bomb_attack_is_prevented()`

**Attack Vector:**
```xml
<?xml version="1.0"?>
<!DOCTYPE lolz [
  <!ENTITY lol "lol">
  <!ENTITY lol2 "&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;&lol;">
  <!ENTITY lol3 "&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;&lol2;">
]>
<lolz>&lol3;</lolz>
```

**Target:** XML parsing (if used)

**How Attack Works:**
- Exponential entity expansion causes memory exhaustion
- Small XML file expands to gigabytes in memory
- Causes Denial of Service

**Expected Defense:**
- XML parser configured with entity expansion limits
- Disable external entity loading
- Set memory limits for parsing

**Status:** Test marked incomplete - verify XML parser configuration

**Validates:** DoS prevention best practices

---

## Summary Statistics

### Total Attack Scenarios: 30

**By Category:**
- XSS Attacks: 10 scenarios
- SQL Injection: 2 scenarios
- Path Traversal: 4 scenarios
- Attribute Injection: 4 scenarios
- Encrypted Data Tampering: 4 scenarios
- Additional Security: 6 scenarios

**By Severity:**
- Critical: 18 scenarios (XSS, SQL Injection, Path Traversal, Encryption)
- High: 8 scenarios (Attribute Injection, File Upload)
- Medium: 4 scenarios (CSRF, Mass Assignment, DoS)

**Test Coverage:**
- Fully Implemented: 28 tests
- Marked Incomplete: 2 tests (Polyglot, XML Bomb - require advanced scanning)

---

## Running the Tests

### Run All Penetration Tests
```bash
php artisan test --group=penetration
```

### Run by Category
```bash
php artisan test --group=xss
php artisan test --group=sql-injection
php artisan test --group=path-traversal
php artisan test --group=attribute-injection
php artisan test --group=encryption
php artisan test --group=file-upload
```

### Run Critical Tests Only
```bash
php artisan test --group=critical
```

---

## Security Recommendations

### Immediate Actions
1. ✅ All XSS vulnerabilities patched
2. ✅ SQL injection prevention implemented
3. ✅ Path traversal protection active
4. ✅ Attribute injection blocked
5. ✅ Encryption integrity checking enabled

### Future Enhancements
1. Consider virus scanning integration for file uploads
2. Implement advanced polyglot file detection
3. Add rate limiting for form submissions
4. Implement Content Security Policy (CSP) headers
5. Add security monitoring and alerting

### Monitoring
- Log all security events (XSS attempts, path traversal, etc.)
- Monitor for patterns indicating attacks
- Set up alerts for repeated attack attempts
- Regular security audits and penetration testing

---

## References

- OWASP Top 10: https://owasp.org/www-project-top-ten/
- OWASP XSS Prevention Cheat Sheet
- OWASP SQL Injection Prevention Cheat Sheet
- OWASP File Upload Security
- Laravel Security Best Practices

---

**Document Version:** 1.0  
**Last Updated:** 2024  
**Maintained By:** Security Team  
**Related Files:** 
- `tests/Security/PenetrationTest.php`
- `docs/COMPONENTS/FORM/AUDIT/DESIGN.md`
- `docs/COMPONENTS/FORM/AUDIT/REQUIREMENTS.md`
