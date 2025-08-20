#!/bin/bash

# Sync Production BricksSync: Export on production server, then pull files locally
# This ensures you get the latest client changes directly from the database

# Configuration - Load from environment file
CONFIG_FILE="$(dirname "$0")/../.env"

# Load configuration
if [[ -f "$CONFIG_FILE" ]]; then
    source "$CONFIG_FILE"
else
    echo "ERROR: Configuration file not found: $CONFIG_FILE"
    exit 1
fi

# Validate required configuration
if [[ -z "$PROD_SERVER_IP" || -z "$PROD_USERNAME" ]]; then
    echo "ERROR: Missing required configuration in $CONFIG_FILE"
    exit 1
fi

# Step 1: Export BricksSync data on production server
ssh -T "$PROD_USERNAME@$PROD_SERVER_IP" -p "$PROD_PORT" << 'EOF'
    cd /www/growtherapy_429/public
    wp brickssync settings export --allow-root
    wp brickssync templates export --allow-root
EOF

# Step 2: Pull the exported files directly to local directory

# Download the exported files directly to local brickssync-json directory
scp -P "$PROD_PORT" -r "$PROD_USERNAME@$PROD_SERVER_IP:/www/growtherapy_429/public/wp-content/themes/growtherapy/brickssync-json/*" wp-content/themes/growtherapy/brickssync-json/

echo "SUCCESS: Production BricksSync files synced to local directory"
