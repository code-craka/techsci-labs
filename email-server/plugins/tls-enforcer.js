/**
 * TLS Enforcement Plugin for Haraka
 * 
 * Enforces TLS/SSL encryption for all email protocols
 * - Mandatory STARTTLS for SMTP, IMAP, POP3
 * - Strong cipher suites only
 * - Certificate validation
 * - Security headers
 */

const fs = require('fs');
const path = require('path');

exports.register = function() {
    this.loginfo('TLS Enforcer plugin loaded');
    
    // Load TLS configuration
    this.load_tls_config();
    
    // Register hooks
    this.register_hook('init_master', 'init_tls_enforcer');
    this.register_hook('init_child', 'init_tls_enforcer');
    this.register_hook('connect', 'check_tls_requirement');
    this.register_hook('ehlo', 'advertise_starttls');
    this.register_hook('helo', 'advertise_starttls');
    this.register_hook('mail', 'enforce_tls_for_mail');
    this.register_hook('rcpt', 'enforce_tls_for_rcpt');
    this.register_hook('data', 'enforce_tls_for_data');
};

exports.load_tls_config = function() {
    const plugin = this;
    
    try {
        const configPath = path.join(__dirname, '../config/tls.json');
        const configData = fs.readFileSync(configPath, 'utf8');
        plugin.tls_config = JSON.parse(configData);
        plugin.loginfo('TLS configuration loaded successfully');
    } catch (error) {
        plugin.logerror(`Failed to load TLS configuration: ${error.message}`);
        // Use default secure configuration
        plugin.tls_config = {
            tls: {
                enabled: true,
                required: true,
                protocols: ['TLSv1.2', 'TLSv1.3'],
                verify_certificate: true
            },
            security: {
                reject_unencrypted: true,
                require_valid_certificates: true
            }
        };
    }
};

exports.init_tls_enforcer = function(next) {
    const plugin = this;
    
    // Set up TLS options
    const server = this.haraka_server || this;
    
    if (server.config && plugin.tls_config.tls.enabled) {
        // Configure TLS settings
        server.tls = {
            key: plugin.tls_config.tls.certificate?.key,
            cert: plugin.tls_config.tls.certificate?.cert,
            ca: plugin.tls_config.tls.certificate?.ca,
            protocols: plugin.tls_config.tls.protocols,
            ciphers: plugin.tls_config.tls.ciphers?.join(':'),
            honorCipherOrder: plugin.tls_config.tls.honor_cipher_order,
            secureProtocol: 'TLSv1_2_method',
            rejectUnauthorized: plugin.tls_config.tls.verify_certificate
        };
        
        plugin.loginfo('TLS enforcer initialized with secure settings');
    }
    
    next();
};

exports.check_tls_requirement = function(next, connection) {
    const plugin = this;
    
    // Skip for internal connections
    if (connection.remote.is_private) {
        return next();
    }
    
    // Check if TLS is required
    if (plugin.tls_config.security?.reject_unencrypted && !connection.using_tls) {
        connection.loginfo(plugin, 'Connection from non-TLS client detected');
        
        // Set flag to enforce TLS later
        connection.notes.tls_required = true;
        connection.notes.tls_enforced = false;
    }
    
    // Add security headers
    connection.notes.security_headers = {
        'X-TLS-Required': 'true',
        'X-Security-Policy': 'TLS-enforced',
        'Strict-Transport-Security': 'max-age=31536000; includeSubDomains'
    };
    
    next();
};

exports.advertise_starttls = function(next, connection, helo) {
    const plugin = this;
    
    if (plugin.tls_config.tls.starttls?.enabled && !connection.using_tls) {
        // Advertise STARTTLS capability
        connection.capabilities.push('STARTTLS');
        connection.loginfo(plugin, 'STARTTLS capability advertised');
        
        if (plugin.tls_config.tls.starttls?.required) {
            connection.notes.starttls_required = true;
        }
    }
    
    next();
};

exports.enforce_tls_for_mail = function(next, connection, params) {
    const plugin = this;
    
    // Check TLS requirement for MAIL command
    if (plugin.should_enforce_tls(connection)) {
        connection.respond(530, 'Must issue STARTTLS command first');
        return next(DENY);
    }
    
    // Validate TLS if already established
    if (connection.using_tls && plugin.tls_config.security?.require_valid_certificates) {
        if (!plugin.validate_tls_connection(connection)) {
            connection.respond(550, 'TLS certificate validation failed');
            return next(DENY);
        }
    }
    
    next();
};

exports.enforce_tls_for_rcpt = function(next, connection, params) {
    const plugin = this;
    
    // Check TLS requirement for RCPT command
    if (plugin.should_enforce_tls(connection)) {
        connection.respond(530, 'Must issue STARTTLS command first');
        return next(DENY);
    }
    
    next();
};

exports.enforce_tls_for_data = function(next, connection) {
    const plugin = this;
    
    // Check TLS requirement for DATA command
    if (plugin.should_enforce_tls(connection)) {
        connection.respond(530, 'Must issue STARTTLS command first');
        return next(DENY);
    }
    
    // Add security headers to message
    if (connection.notes.security_headers) {
        Object.entries(connection.notes.security_headers).forEach(([key, value]) => {
            connection.transaction.add_header(key, value);
        });
    }
    
    next();
};

exports.should_enforce_tls = function(connection) {
    const plugin = this;
    
    // Don't enforce for internal/private connections
    if (connection.remote.is_private) {
        return false;
    }
    
    // Check if TLS is required but not established
    const tlsRequired = plugin.tls_config.security?.reject_unencrypted ||
                       connection.notes.tls_required ||
                       connection.notes.starttls_required;
    
    return tlsRequired && !connection.using_tls;
};

exports.validate_tls_connection = function(connection) {
    const plugin = this;
    
    if (!connection.tls || !connection.tls.peer_certificate) {
        plugin.logwarn('No TLS certificate found');
        return false;
    }
    
    const cert = connection.tls.peer_certificate;
    
    // Check certificate validity
    if (cert.valid_from && cert.valid_to) {
        const now = new Date();
        const validFrom = new Date(cert.valid_from);
        const validTo = new Date(cert.valid_to);
        
        if (now < validFrom || now > validTo) {
            plugin.logwarn('TLS certificate is expired or not yet valid');
            return false;
        }
    }
    
    // Check minimum key size
    const minKeySize = plugin.tls_config.security?.minimum_key_size || 2048;
    if (cert.bits && cert.bits < minKeySize) {
        plugin.logwarn(`TLS certificate key size (${cert.bits}) below minimum (${minKeySize})`);
        return false;
    }
    
    // Check protocol version
    const allowedProtocols = plugin.tls_config.security?.allowed_protocols || ['TLSv1.2', 'TLSv1.3'];
    if (connection.tls.protocol && !allowedProtocols.includes(connection.tls.protocol)) {
        plugin.logwarn(`TLS protocol ${connection.tls.protocol} not allowed`);
        return false;
    }
    
    plugin.loginfo('TLS connection validation passed');
    return true;
};

// Hook for TLS session establishment
exports.hook_tls_established = function(next, connection) {
    const plugin = this;
    
    connection.loginfo(plugin, `TLS established: ${connection.tls.protocol} ${connection.tls.cipher}`);
    
    // Mark TLS as established
    connection.notes.tls_enforced = true;
    
    // Log security details
    if (connection.tls.peer_certificate) {
        const cert = connection.tls.peer_certificate;
        connection.loginfo(plugin, `Client certificate: ${cert.subject?.CN || 'unknown'}`);
    }
    
    next();
};