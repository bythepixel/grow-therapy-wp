#!/bin/bash

# WordPress Plugin Deployment Script
# This script handles Composer-based plugin installation and verification

# Color codes for logging
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# All required plugins
REQUIRED_PLUGINS=(
    "admin-columns-pro"
    "advanced-custom-fields-pro"
    "automaticcss-plugin"
    "brickssync"
    "easy-table-of-contents"
    "enable-media-replace"
    "gravityforms"
    "gravityformssurvey"
    "gravityformswebhooks"
    "happyfiles-pro"
    "perfmatters"
    "redirection"
    "revisionary-pro"
    "seo-by-rank-math"
    "seo-by-rank-math-pro"
    "simple-page-ordering"
    "trustpilot-reviews"
    "user-role-editor"
    "visual-web-optimizer"
    "wp-all-export-pro"
    "wpae-acf-add-on"
    "wpae-gravity-forms-export-addon"
    "wpae-user-add-on-pro"
    "wp-all-import-pro"
    "wpai-acf-add-on"
    "wpai-gravity-forms-import-addon"
    "wpai-user-add-on"
    "yoast-seo-settings-xml-csv-import"
    "wp-migrate-db-pro"
    "wp-graphql"
    "wpgraphql-acf"
    "wpgraphql-smart-cache"
    "wordpress-seo"
    "wordpress-seo-premium"
)

# Logging functions
log_info() {
    echo -e "${BLUE}INFO: $1${NC}"
}

log_success() {
    echo -e "${GREEN}SUCCESS: $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}WARNING: $1${NC}"
}

log_error() {
    echo -e "${RED}ERROR: $1${NC}"
}

log_phase() {
    echo -e "${BLUE}PHASE $1: $2${NC}"
}

# Check if Composer is available
check_composer() {
    if command -v composer &> /dev/null; then
        log_success "Composer found, version: $(composer --version | head -n1)"
        return 0
    else
        log_error "Composer not found - deployment stopped"
        log_info "Action required: Contact Kinsta support to install Composer"
        return 1
    fi
}

# Check which plugins need to be installed
check_missing_plugins() {
    log_info "Checking plugin availability..."
    
    local missing_plugins=()
    
    for plugin in "${REQUIRED_PLUGINS[@]}"; do
        if [ -d "wp-content/plugins/$plugin" ]; then
            log_success "$plugin: Available"
        else
            log_warning "$plugin: Missing"
            missing_plugins+=("$plugin")
        fi
    done
    
    echo "${#missing_plugins[@]}"
}

# Install Composer dependencies if needed
install_dependencies() {
    local missing_count="$1"
    
    if [ "$missing_count" -gt 0 ]; then
        log_info "Installing $missing_count missing plugins via Composer..."
        
        if COMPOSER_MEMORY_LIMIT="-1" composer install --no-dev --optimize-autoloader --no-interaction; then
            log_success "Composer dependencies installed successfully"
        else
            log_error "Composer install failed - deployment stopped"
            log_info "Common causes:"
            log_info "  - Plugin not found in repository"
            log_info "  - Dependency conflicts between plugins"
            log_info "  - Server requirements not met (PHP version, extensions)"
            log_info "  - Memory limits exceeded"
            exit 1
        fi
    else
        log_success "All plugins are already available"
    fi
}

# Main plugin deployment function
deploy_plugins() {
    log_phase "4" "Plugin dependency management"
    
    # Check Composer availability
    if ! check_composer; then
        exit 1
    fi
    
    # Check which plugins are missing
    local missing_count
    missing_count=$(check_missing_plugins)
    
    # Install dependencies if needed
    install_dependencies "$missing_count"
    
    log_success "Plugin deployment completed successfully!"
}

# Run plugin deployment if script is executed directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    log_info "Starting plugin deployment"
    deploy_plugins
fi
