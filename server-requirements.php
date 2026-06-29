<?php
// ============================================
// SECURITY: REMOVE THIS FILE AFTER USE!
// This file exposes sensitive server information
// ============================================

// Security: Access Token - MUST be provided in URL
// For your support team: Share this token with clients when providing support
// Client accesses: https://their-domain.com/server-requirements.php?token=XXXXX
$accessToken = 'WorkDo1232';
// Generate secure token: openssl rand -hex 32
// Or use: https://www.random.org/strings/

// Security: Check if token is provided and valid
if (!isset($_GET['token']) || $_GET['token'] !== $accessToken) {
    http_response_code(403);
    
    // Get client IP for logging
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $clientIP = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    
    // Log unauthorized access attempt
    error_log("[SECURITY] Unauthorized access to server-requirements.php from IP: $clientIP at " . date('Y-m-d H:i:s'));
    
    // Show generic error (don't reveal that file exists)
    die('<!DOCTYPE html>
    <html>
    <head><title>Access Denied</title></head>
    <body style="font-family: Arial; text-align: center; padding: 50px;">
        <h1>403 - Access Denied</h1>
        <p>You do not have permission to access this resource.</p>
        <p style="color: #666; font-size: 12px; margin-top: 30px;">If you need support, please contact the system administrator.</p>
    </body>
    </html>');
}

// Disable error display (only log)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$config = require __DIR__ . '/config/installer.php';
$verification = file_exists(__DIR__ . '/config/verification.php') ? require __DIR__ . '/config/verification.php' : [];
$minPhpVersion = $config['core']['minPhpVersion'];
$requiredExtensions = $config['requirements']['php'];
$permissions = $config['permissions'];
$systemName = $verification['system'] ?? 'Application';
$systemVersion = $verification['system_version'] ?? 'Unknown';

$results = [
    'server' => [],
    'php' => [],
    'database' => [],
    'extensions' => [],
    'settings' => [],
    'permissions' => [],
    'webserver' => []
];

// Server Type Detection
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
if (stripos($serverSoftware, 'apache') !== false) {
    $results['server']['type'] = 'Apache';
    $results['server']['version'] = $serverSoftware;
} elseif (stripos($serverSoftware, 'nginx') !== false) {
    $results['server']['type'] = 'Nginx';
    $results['server']['version'] = $serverSoftware;
} elseif (stripos($serverSoftware, 'litespeed') !== false) {
    $results['server']['type'] = 'LiteSpeed';
    $results['server']['version'] = $serverSoftware;
} elseif (stripos($serverSoftware, 'microsoft-iis') !== false) {
    $results['server']['type'] = 'IIS';
    $results['server']['version'] = $serverSoftware;
} else {
    $results['server']['type'] = 'Other/Unknown';
    $results['server']['version'] = $serverSoftware;
}
$results['server']['sapi'] = php_sapi_name();

// Current Domain Detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://';
$results['server']['domain'] = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'Unknown';
$results['server']['full_url'] = $protocol . $results['server']['domain'];
$results['server']['server_ip'] = $_SERVER['SERVER_ADDR'] ?? gethostbyname($_SERVER['SERVER_NAME'] ?? 'localhost');

// PHP Version Check
$results['php']['current'] = PHP_VERSION;
$results['php']['required'] = $minPhpVersion;
$results['php']['status'] = version_compare(PHP_VERSION, $minPhpVersion, '>=');

// MySQL/MariaDB Version Check
$results['database']['mysqli_available'] = extension_loaded('mysqli');
$results['database']['pdo_mysql_available'] = extension_loaded('pdo_mysql');

// Try to get MySQL version without credentials using command line
exec('mysql --version 2>&1', $mysqlVersionOutput, $mysqlReturn);
if ($mysqlReturn === 0 && !empty($mysqlVersionOutput[0])) {
    // Parse version from output like: mysql  Ver 8.0.36 for Linux on x86_64 (MySQL Community Server - GPL)
    if (preg_match('/Ver ([0-9.]+)/', $mysqlVersionOutput[0], $matches)) {
        $results['database']['version'] = $matches[1];
    } else {
        $results['database']['version'] = trim($mysqlVersionOutput[0]);
    }
} else {
    // Try MariaDB
    exec('mariadb --version 2>&1', $mariaVersionOutput, $mariaReturn);
    if ($mariaReturn === 0 && !empty($mariaVersionOutput[0])) {
        if (preg_match('/Ver ([0-9.]+)/', $mariaVersionOutput[0], $matches)) {
            $results['database']['version'] = 'MariaDB ' . $matches[1];
        } else {
            $results['database']['version'] = trim($mariaVersionOutput[0]);
        }
    } else {
        $results['database']['version'] = 'Installed (version detection requires mysql client)';
    }
}

// PHP Extensions Check
foreach ($requiredExtensions as $ext) {
    $results['extensions'][$ext] = extension_loaded($ext);
}

// PHP Settings
$results['settings']['upload_max_filesize'] = ini_get('upload_max_filesize');
$results['settings']['post_max_size'] = ini_get('post_max_size');
$results['settings']['max_file_uploads'] = ini_get('max_file_uploads');
$results['settings']['memory_limit'] = ini_get('memory_limit');
$results['settings']['max_execution_time'] = ini_get('max_execution_time');
$results['settings']['max_input_time'] = ini_get('max_input_time');

// Directory Permissions Check
foreach ($permissions as $dir => $required) {
    $path = __DIR__ . '/' . $dir;
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -3);
        $results['permissions'][$dir] = [
            'current' => $perms,
            'required' => $required,
            'status' => $perms >= $required
        ];
    } else {
        $results['permissions'][$dir] = ['status' => false, 'error' => 'Directory not found'];
    }
}

// Web Server Modules Check
if ($results['server']['type'] === 'Apache' && function_exists('apache_get_modules')) {
    $results['webserver']['mod_rewrite'] = in_array('mod_rewrite', apache_get_modules());
} elseif ($results['server']['type'] === 'Nginx') {
    $results['webserver']['mod_rewrite'] = 'Nginx uses try_files (check nginx.conf)';
} else {
    $results['webserver']['mod_rewrite'] = 'Cannot detect (check server config)';
}

// Node.js Version Check - Direct command execution
$results['node'] = [];
$results['node']['installed'] = false;
$results['node']['version'] = 'Not installed';
$results['node']['path'] = 'N/A';
$results['node']['npm_version'] = 'Not installed';
$results['node']['npm_path'] = 'N/A';

// Try multiple methods to get Node.js version
$nodeMethods = [];

// Method 1: Direct node -v
exec('node -v 2>&1', $nodeOutput1, $nodeReturn1);
if ($nodeReturn1 === 0 && !empty($nodeOutput1[0])) {
    $results['node']['installed'] = true;
    $results['node']['version'] = trim($nodeOutput1[0]);
    $nodeMethods['exec_node_v'] = trim($nodeOutput1[0]);
    
    exec('which node 2>&1', $whichNode);
    if (!empty($whichNode[0])) {
        $results['node']['path'] = trim($whichNode[0]);
    }
}

// Method 2: shell_exec
$nodeOutput2 = shell_exec('node -v 2>&1');
if ($nodeOutput2) {
    $nodeMethods['shell_exec_node_v'] = trim($nodeOutput2);
    if (!$results['node']['installed']) {
        $results['node']['installed'] = true;
        $results['node']['version'] = trim($nodeOutput2);
    }
}

// Method 3: Find all node installations using which -a
exec('which -a node 2>&1', $allNodePaths, $whichAllReturn);
if ($whichAllReturn === 0 && !empty($allNodePaths)) {
    foreach ($allNodePaths as $nodePath) {
        $nodePath = trim($nodePath);
        if (file_exists($nodePath)) {
            exec("$nodePath -v 2>&1", $pathOutput, $pathReturn);
            if ($pathReturn === 0 && !empty($pathOutput[0])) {
                $version = trim($pathOutput[0]);
                $pathKey = str_replace('/', '_', $nodePath);
                $nodeMethods[$pathKey] = $version;
                
                // Use this if not already found or if version is higher
                if (!$results['node']['installed'] || version_compare(ltrim($version, 'v'), ltrim($results['node']['version'], 'v'), '>')) {
                    $results['node']['installed'] = true;
                    $results['node']['version'] = $version;
                    $results['node']['path'] = $nodePath;
                }
            }
            unset($pathOutput);
        }
    }
}

$results['node']['all_methods'] = $nodeMethods;

// NPM Version Check
exec('npm -v 2>&1', $npmOutput, $npmReturn);
if ($npmReturn === 0 && !empty($npmOutput[0])) {
    $results['node']['npm_version'] = trim($npmOutput[0]);
    
    exec('which npm 2>&1', $whichNpm);
    if (!empty($whichNpm[0])) {
        $results['node']['npm_path'] = trim($whichNpm[0]);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Server Requirements Check</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: #f8f9fa;
            padding: 20px;
            line-height: 1.6;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 8px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header { 
            background: #2c3e50; 
            color: white; 
            padding: 30px; 
            text-align: center;
        }
        .header h1 { 
            font-size: 28px; 
            margin-bottom: 8px; 
            font-weight: 600;
        }
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        .content { 
            padding: 30px;
        }
        .section { 
            margin-bottom: 30px;
        }
        .section h2 { 
            color: #2c3e50; 
            font-size: 18px; 
            margin-bottom: 15px; 
            padding-bottom: 8px; 
            border-bottom: 2px solid #e9ecef;
            font-weight: 600;
        }
        table { 
            width: 100%; 
            border-collapse: collapse;
        }
        th, td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #e9ecef;
        }
        th { 
            background: #f8f9fa; 
            font-weight: 600; 
            color: #495057;
            font-size: 13px;
        }
        tr:last-child td {
            border-bottom: none;
        }
        .badge { 
            display: inline-block; 
            padding: 4px 10px; 
            border-radius: 4px; 
            font-size: 11px; 
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-success { 
            background: #d4edda; 
            color: #155724;
        }
        .badge-danger { 
            background: #f8d7da; 
            color: #721c24;
        }
        .badge-warning { 
            background: #fff3cd; 
            color: #856404;
        }
        .summary { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; 
            margin-bottom: 30px;
        }
        .summary-card { 
            background: #f8f9fa; 
            padding: 20px; 
            border-radius: 6px; 
            text-align: center;
            border: 1px solid #e9ecef;
        }
        .summary-card h3 { 
            font-size: 28px; 
            margin-bottom: 5px; 
            color: #2c3e50;
            font-weight: 600;
        }
        .summary-card p { 
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        details {
            margin-top: 10px;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 10px;
            background: #f8f9fa;
        }
        summary {
            cursor: pointer;
            font-weight: 600;
            padding: 5px;
            font-size: 13px;
            color: #495057;
        }
        .method-list {
            padding: 10px;
            font-family: monospace;
            font-size: 12px;
        }
        .method-item {
            padding: 6px;
            background: white;
            margin: 5px 0;
            border-radius: 4px;
            border-left: 2px solid #2c3e50;
        }
        .status-box {
            padding: 20px;
            border-radius: 6px;
            text-align: center;
            border: 1px solid;
        }
        .status-box h3 {
            font-size: 18px;
            margin-bottom: 8px;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        code {
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
            border: 1px solid #e9ecef;
        }
        a {
            color: #2c3e50;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Server Requirements Check</h1>
            <p><strong><?php echo $systemName; ?></strong> - Version <?php echo $systemVersion; ?></p>
            <p style="margin-top: 5px; font-size: 14px; opacity: 0.9;">Complete system requirements analysis</p>
        </div>
        
        <div class="content">
            <!-- System Information -->
            <div class="section">
                <h2>📦 Application Information</h2>
                <table>
                    <tr>
                        <th>Item</th>
                        <th>Value</th>
                    </tr>
                    <tr>
                        <td>System Name</td>
                        <td><strong><?php echo $systemName; ?></strong></td>
                    </tr>
                    <tr>
                        <td>System Version</td>
                        <td><strong><?php echo $systemVersion; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Required PHP Version</td>
                        <td><strong><?php echo $minPhpVersion; ?>+</strong></td>
                    </tr>
                </table>
            </div>

            <div class="summary">
                <div class="summary-card">
                    <h3><?php echo $results['server']['type']; ?></h3>
                    <p>Web Server</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo PHP_VERSION; ?></h3>
                    <p>PHP Version</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo $results['node']['version']; ?></h3>
                    <p>Node.js Version</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo $results['settings']['upload_max_filesize']; ?></h3>
                    <p>Max Upload Size</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo $results['settings']['memory_limit']; ?></h3>
                    <p>Memory Limit</p>
                </div>
            </div>

            <!-- Server Type -->
            <div class="section">
                <h2>🖥️ Web Server</h2>
                <table>
                    <tr>
                        <th>Item</th>
                        <th>Value</th>
                    </tr>
                    <tr>
                        <td>Current Domain</td>
                        <td><strong><?php echo $results['server']['domain']; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Full URL</td>
                        <td><a href="<?php echo $results['server']['full_url']; ?>" target="_blank"><?php echo $results['server']['full_url']; ?></a></td>
                    </tr>
                    <tr>
                        <td>Server IP</td>
                        <td><?php echo $results['server']['server_ip']; ?></td>
                    </tr>
                    <tr>
                        <td>Server Type</td>
                        <td><strong><?php echo $results['server']['type']; ?></strong></td>
                    </tr>
                    <tr>
                        <td>Server Software</td>
                        <td><?php echo $results['server']['version']; ?></td>
                    </tr>
                    <tr>
                        <td>PHP SAPI</td>
                        <td><?php echo $results['server']['sapi']; ?></td>
                    </tr>
                </table>
            </div>

            <!-- PHP Version -->
            <div class="section">
                <h2>📦 PHP Version</h2>
                <table>
                    <tr>
                        <th>Item</th>
                        <th>Value</th>
                        <th>Status</th>
                    </tr>
                    <tr>
                        <td>Current PHP Version</td>
                        <td><?php echo $results['php']['current']; ?></td>
                        <td>
                            <?php if ($results['php']['status']): ?>
                                <span class="badge badge-success">✓ PASS</span>
                            <?php else: ?>
                                <span class="badge badge-danger">✗ FAIL</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Required PHP Version</td>
                        <td><?php echo $results['php']['required']; ?>+</td>
                        <td>-</td>
                    </tr>
                </table>
            </div>

            <!-- Database -->
            <div class="section">
                <h2>🟢 Node.js & NPM</h2>
                <table>
                    <tr>
                        <th>Item</th>
                        <th>Value</th>
                        <th>Status</th>
                    </tr>
                    <tr>
                        <td>Node.js Version</td>
                        <td><strong style="color: #667eea; font-size: 16px;"><?php echo $results['node']['version']; ?></strong></td>
                        <td>
                            <?php if ($results['node']['installed']): ?>
                                <span class="badge badge-success">✓ INSTALLED</span>
                            <?php else: ?>
                                <span class="badge badge-danger">✗ NOT INSTALLED</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Node.js Path</td>
                        <td><small style="color: #666;"><?php echo $results['node']['path']; ?></small></td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>NPM Version</td>
                        <td><strong style="color: #667eea; font-size: 16px;"><?php echo $results['node']['npm_version']; ?></strong></td>
                        <td>
                            <?php if ($results['node']['npm_version'] !== 'Not installed'): ?>
                                <span class="badge badge-success">✓ INSTALLED</span>
                            <?php else: ?>
                                <span class="badge badge-danger">✗ NOT INSTALLED</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>NPM Path</td>
                        <td><small style="color: #666;"><?php echo $results['node']['npm_path']; ?></small></td>
                        <td>-</td>
                    </tr>
                    <?php if (!empty($results['node']['all_methods'])): ?>
                    <tr>
                        <td colspan="3">
                            <details>
                                <summary>🔍 Detection Methods (Click to expand)</summary>
                                <div class="method-list">
                                    <?php foreach ($results['node']['all_methods'] as $method => $version): ?>
                                        <div class="method-item"><strong><?php echo htmlspecialchars($method); ?>:</strong> <?php echo htmlspecialchars($version); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </details>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Database -->
            <div class="section">
                <h2>🗄️ Database (MySQL/MariaDB)</h2>
                <table>
                    <tr>
                        <th>Item</th>
                        <th>Value</th>
                        <th>Status</th>
                    </tr>
                    <tr>
                        <td>MySQLi Extension</td>
                        <td><?php echo $results['database']['mysqli_available'] ? 'Available' : 'Not Available'; ?></td>
                        <td>
                            <?php if ($results['database']['mysqli_available']): ?>
                                <span class="badge badge-success">✓ AVAILABLE</span>
                            <?php else: ?>
                                <span class="badge badge-danger">✗ NOT AVAILABLE</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>PDO MySQL Extension</td>
                        <td><?php echo $results['database']['pdo_mysql_available'] ? 'Available' : 'Not Available'; ?></td>
                        <td>
                            <?php if ($results['database']['pdo_mysql_available']): ?>
                                <span class="badge badge-success">✓ AVAILABLE</span>
                            <?php else: ?>
                                <span class="badge badge-danger">✗ NOT AVAILABLE</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>Database Version</td>
                        <td><?php echo $results['database']['version']; ?></td>
                        <td>-</td>
                    </tr>
                </table>
            </div>

            <!-- PHP Extensions -->
            <div class="section">
                <h2>🔌 PHP Extensions</h2>
                <table>
                    <tr>
                        <th>Extension</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($results['extensions'] as $ext => $loaded): ?>
                    <tr>
                        <td><?php echo $ext; ?></td>
                        <td>
                            <?php if ($loaded): ?>
                                <span class="badge badge-success">✓ ENABLED</span>
                            <?php else: ?>
                                <span class="badge badge-danger">✗ DISABLED</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- PHP Settings -->
            <div class="section">
                <h2>⚙️ PHP Configuration</h2>
                <table>
                    <tr>
                        <th>Setting</th>
                        <th>Value</th>
                    </tr>
                    <?php foreach ($results['settings'] as $setting => $value): ?>
                    <tr>
                        <td><?php echo str_replace('_', ' ', ucfirst($setting)); ?></td>
                        <td><strong><?php echo $value; ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Directory Permissions -->
            <div class="section">
                <h2>📁 Directory Permissions</h2>
                <table>
                    <tr>
                        <th>Directory</th>
                        <th>Current</th>
                        <th>Required</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($results['permissions'] as $dir => $perm): ?>
                    <tr>
                        <td><?php echo $dir; ?></td>
                        <td><?php echo isset($perm['current']) ? $perm['current'] : 'N/A'; ?></td>
                        <td><?php echo isset($perm['required']) ? $perm['required'] : 'N/A'; ?></td>
                        <td>
                            <?php if ($perm['status']): ?>
                                <span class="badge badge-success">✓ OK</span>
                            <?php else: ?>
                                <span class="badge badge-danger">✗ <?php echo $perm['error'] ?? 'INSUFFICIENT'; ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- Web Server Modules -->
            <div class="section">
                <h2>🌐 Web Server Configuration</h2>
                <table>
                    <tr>
                        <th>Item</th>
                        <th>Status</th>
                    </tr>
                    <tr>
                        <td><?php echo $results['server']['type'] === 'Apache' ? 'mod_rewrite' : 'URL Rewriting'; ?></td>
                        <td>
                            <?php if ($results['webserver']['mod_rewrite'] === true): ?>
                                <span class="badge badge-success">✓ ENABLED</span>
                            <?php elseif ($results['webserver']['mod_rewrite'] === false): ?>
                                <span class="badge badge-danger">✗ DISABLED</span>
                            <?php else: ?>
                                <span class="badge badge-warning">⚠ <?php echo $results['webserver']['mod_rewrite']; ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Overall Status -->
            <div class="section">
                <h2>📊 Overall Status</h2>
                <?php
                $allPassed = $results['php']['status'] && 
                             $results['database']['mysqli_available'] &&
                             !in_array(false, $results['extensions']);
                ?>
                <?php if ($allPassed): ?>
                    <div class="status-box status-success">
                        <h3>✅ All Requirements Met!</h3>
                        <p>Your server meets all the requirements to run this application.</p>
                    </div>
                <?php else: ?>
                    <div class="status-box status-error">
                        <h3>⚠️ Some Requirements Not Met</h3>
                        <p>Please fix the issues marked as FAIL or DISABLED above.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
