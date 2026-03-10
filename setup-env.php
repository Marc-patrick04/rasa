<?php
/**
 * Environment Setup Script for RASA Admin Panel
 * 
 * This script helps configure the application for both local development
 * and production with Neon PostgreSQL.
 */

// Check if this is being run as a CLI script
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

echo "=== RASA Admin Panel Environment Setup ===\n\n";

// Function to get user input
function getInput($prompt, $default = '') {
    echo $prompt;
    if ($default) {
        echo " (default: $default)";
    }
    echo ": ";
    $handle = fopen("php://stdin", "r");
    $input = trim(fgets($handle));
    fclose($handle);
    return $input ?: $default;
}

// Function to write environment file
function writeEnvFile($content) {
    $envFile = '.env';
    if (file_exists($envFile)) {
        echo "Warning: .env file already exists. Backing up...\n";
        copy($envFile, $envFile . '.backup.' . time());
    }
    
    if (file_put_contents($envFile, $content)) {
        echo "✓ Environment file created: $envFile\n";
        return true;
    } else {
        echo "✗ Failed to create environment file\n";
        return false;
    }
}

// Ask user for setup type
echo "Choose setup type:\n";
echo "1. Local Development (PostgreSQL)\n";
echo "2. Production (Neon PostgreSQL)\n";
echo "3. Custom Configuration\n";

$setupType = getInput("Enter choice (1-3)", '1');

if ($setupType === '1') {
    // Local Development Setup
    echo "\n=== Local Development Setup ===\n";
    
    $dbHost = getInput("Database host", 'localhost');
    $dbName = getInput("Database name", 'rasa_db');
    $dbUser = getInput("Database user", 'postgres');
    $dbPass = getInput("Database password", 'numugisha');
    $dbPort = getInput("Database port", '5432');
    
    $envContent = <<<EOF
# Local Development Configuration
ENVIRONMENT=development
DB_HOST=$dbHost
DB_NAME=$dbName
DB_USER=$dbUser
DB_PASS=$dbPass
DB_PORT=$dbPort
DB_SSLMODE=prefer
SITE_URL=http://localhost/rasa
EOF;

    writeEnvFile($envContent);
    
    echo "\n✓ Local development configuration complete!\n";
    echo "Next steps:\n";
    echo "1. Create database: CREATE DATABASE $dbName;\n";
    echo "2. Create user: CREATE USER $dbUser WITH PASSWORD '$dbPass';\n";
    echo "3. Import schema: psql -U $dbUser -d $dbName -f dd.sql\n";
    echo "4. Visit: http://localhost/rasa\n\n";

} elseif ($setupType === '2') {
    // Neon PostgreSQL Setup
    echo "\n=== Neon PostgreSQL Setup ===\n";
    
    $databaseUrl = getInput("Enter your Neon PostgreSQL DATABASE_URL");
    
    if (empty($databaseUrl)) {
        echo "✗ Database URL is required for Neon PostgreSQL setup\n";
        echo "Get your DATABASE_URL from: https://console.neon.tech\n";
        exit(1);
    }
    
    $envContent = <<<EOF
# Production Configuration (Neon PostgreSQL)
ENVIRONMENT=production
DATABASE_URL=$databaseUrl
SITE_URL=https://your-production-domain.com
EOF;

    writeEnvFile($envContent);
    
    echo "\n✓ Neon PostgreSQL configuration complete!\n";
    echo "Next steps:\n";
    echo "1. Upload all files to your web hosting\n";
    echo "2. Import schema using Neon SQL Editor or psql\n";
    echo "3. Set the SITE_URL to your actual domain\n";
    echo "4. Visit your production domain\n\n";

} elseif ($setupType === '3') {
    // Custom Configuration
    echo "\n=== Custom Configuration ===\n";
    
    $environment = getInput("Environment (development/production)", 'development');
    $siteUrl = getInput("Site URL", 'http://localhost/rasa');
    
    if ($environment === 'production') {
        $databaseUrl = getInput("DATABASE_URL (for production)");
        $envContent = <<<EOF
# Custom Production Configuration
ENVIRONMENT=production
DATABASE_URL=$databaseUrl
SITE_URL=$siteUrl
EOF;
    } else {
        $dbHost = getInput("Database host", 'localhost');
        $dbName = getInput("Database name", 'rasa_db');
        $dbUser = getInput("Database user", 'postgres');
        $dbPass = getInput("Database password", 'numugisha');
        $dbPort = getInput("Database port", '5432');
        $dbSslMode = getInput("SSL Mode", 'prefer');
        
        $envContent = <<<EOF
# Custom Development Configuration
ENVIRONMENT=development
DB_HOST=$dbHost
DB_NAME=$dbName
DB_USER=$dbUser
DB_PASS=$dbPass
DB_PORT=$dbPort
DB_SSLMODE=$dbSslMode
SITE_URL=$siteUrl
EOF;
    }
    
    writeEnvFile($envContent);
    echo "\n✓ Custom configuration complete!\n";

} else {
    echo "✗ Invalid choice. Please run the script again.\n";
    exit(1);
}

// Additional setup instructions
echo "\n=== Additional Setup Instructions ===\n";
echo "1. Ensure all PHP files have proper permissions\n";
echo "2. Make sure your web server can read the files\n";
echo "3. Test the database connection\n";
echo "4. Default admin login:\n";
echo "   Username: admin\n";
echo "   Password: admin123\n\n";

echo "=== Setup Complete! ===\n";
echo "Your RASA Admin Panel is ready to use.\n";
?>