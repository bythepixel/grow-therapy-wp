#!/bin/bash

# WordPress Plugin Deployment Script
# This script handles Composer-based plugin installation and verification

# Color codes for logging
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

log_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

log_error() {
    echo -e "${RED}✗ $1${NC}"
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

# Analyze current plugin state
analyze_plugin_state() {
    log_info "Analyzing current plugin state..."
    
    local plugins_need_update=false
    
    # Check all required plugins from composer.json with versions
    local required_plugins=(
        "admin-columns-pro:6.4.21"
        "advanced-custom-fields-pro:6.5.0.1"
        "automaticcss-plugin:3.3.5"
        "brickssync:1.1.1"
        "easy-table-of-contents:2.0.75"
        "enable-media-replace:4.1.6"
        "gravityforms:2.9.5"
        "gravityformssurvey:4.1.0"
        "gravityformswebhooks:1.5"
        "happyfiles-pro:1.8.3"
        "perfmatters:2.4.9"
        "revisionary-pro:3.7.8"
        "seo-by-rank-math:1.0.251"
        "seo-by-rank-math-pro:3.0.93"
        "redirection:5.5.2"
        "simple-page-ordering:2.7.4"
        "trustpilot-reviews:2.5.927"
        "user-role-editor:4.64.5"
        "visual-web-optimizer:4.8"
        "wp-all-export-pro:1.9.11"
        "wpae-acf-add-on:1.0.6"
        "wpae-gravity-forms-export-addon:1.0.2"
        "wpae-user-add-on-pro:1.0.11"
        "wp-all-import-pro:4.11.5"
        "wpai-acf-add-on:3.4.0"
        "wpai-gravity-forms-import-addon:1.0.2"
        "wpai-user-add-on:1.1.9"
        "yoast-seo-settings-xml-csv-import:1.1.8"
        "wp-migrate-db-pro:2.7.4"
        "wp-graphql:2.3.3"
        "wpgraphql-acf:2.4.1"
        "wpgraphql-smart-cache:2.0.0"
        "wordpress-seo:25.7"
        "wordpress-seo-premium:21.8"
    )
    
    for plugin_info in "${required_plugins[@]}"; do
        local plugin_name="${plugin_info%:*}"
        local expected_version="${plugin_info#*:}"
        
        if [ -d "wp-content/plugins/$plugin_name" ]; then
            # Try to get the actual version from the plugin
            local actual_version=""
            if [ -f "wp-content/plugins/$plugin_name/$plugin_name.php" ]; then
                actual_version=$(grep -o "Version: [0-9.]*" "wp-content/plugins/$plugin_name/$plugin_name.php" | cut -d' ' -f2)
            elif [ -f "wp-content/plugins/$plugin_name/readme.txt" ]; then
                actual_version=$(grep -o "Stable tag: [0-9.]*" "wp-content/plugins/$plugin_name/readme.txt" | cut -d' ' -f3)
            fi
            
            if [ -n "$actual_version" ]; then
                if [ "$actual_version" = "$expected_version" ]; then
                    log_success "$plugin_name: Version $actual_version (correct)"
                else
                    log_warning "$plugin_name: Version $actual_version (expected $expected_version) - will update"
                    plugins_need_update=true
                fi
            else
                log_warning "$plugin_name: Installed but version unknown - will verify"
                plugins_need_update=true
            fi
        else
            log_warning "$plugin_name: Not found, will install version $expected_version"
            plugins_need_update=true
        fi
    done
    
    echo "$plugins_need_update"
}

# Install Composer dependencies
install_dependencies() {
    local plugins_need_update="$1"
    
    if [ "$plugins_need_update" = true ]; then
        log_info "Installing/updating plugin dependencies..."
    else
        log_info "All plugins present, verifying installations..."
    fi
    
    # Try quiet install first, then verbose if it fails
    if COMPOSER_MEMORY_LIMIT="$COMPOSER_MEMORY_LIMIT" composer install --no-dev --optimize-autoloader --no-interaction --quiet 2>/dev/null; then
        log_success "Dependencies processed successfully"
    else
        log_warning "Quiet install failed, attempting verbose mode..."
        if COMPOSER_MEMORY_LIMIT="$COMPOSER_MEMORY_LIMIT" composer install --no-dev --optimize-autoloader --no-interaction; then
            log_success "Dependencies processed successfully (verbose mode)"
        else
            log_error "Composer install failed completely - deployment stopped"
            log_info "Common causes:"
            log_info "  - Plugin version not found in repository"
            log_info "  - Dependency conflicts between plugins"
            log_info "  - Server requirements not met (PHP version, extensions)"
            log_info "  - Memory limits exceeded"
            exit 1
        fi
    fi
}

# List installed plugins
list_plugins() {
    log_info "Current plugin inventory:"
    ls -la wp-content/plugins/ | grep -E "^d" | awk '{print $9}' | grep -v "^\.$" | grep -v "^\.\.$" | grep -v "^index\.php$" | sed 's/^/     - /'
    echo ""
}

# Verify plugin availability
verify_plugins() {
    log_phase "5" "Plugin availability verification"
    log_info "Verifying required plugins are available with correct versions..."
    
    local all_available=true
    
    # Check all required plugins from composer.json with versions
    local required_plugins=(
        "admin-columns-pro:6.4.21"
        "advanced-custom-fields-pro:6.5.0.1"
        "automaticcss-plugin:3.3.5"
        "brickssync:1.1.1"
        "easy-table-of-contents:2.0.75"
        "enable-media-replace:4.1.6"
        "gravityforms:2.9.5"
        "gravityformssurvey:4.1.0"
        "gravityformswebhooks:1.5"
        "happyfiles-pro:1.8.3"
        "perfmatters:2.4.9"
        "revisionary-pro:3.7.8"
        "seo-by-rank-math:1.0.251"
        "seo-by-rank-math-pro:3.0.93"
        "redirection:5.5.2"
        "simple-page-ordering:2.7.4"
        "trustpilot-reviews:2.5.927"
        "user-role-editor:4.64.5"
        "visual-web-optimizer:4.8"
        "wp-all-export-pro:1.9.11"
        "wpae-acf-add-on:1.0.6"
        "wpae-gravity-forms-export-addon:1.0.2"
        "wpae-user-add-on-pro:1.0.11"
        "wp-all-import-pro:4.11.5"
        "wpai-acf-add-on:3.4.0"
        "wpai-gravity-forms-import-addon:1.0.2"
        "wpai-user-add-on:1.1.9"
        "yoast-seo-settings-xml-csv-import:1.1.8"
        "wp-migrate-db-pro:2.7.4"
        "wp-graphql:2.3.3"
        "wpgraphql-acf:2.4.1"
        "wpgraphql-smart-cache:2.0.0"
        "wordpress-seo:25.7"
        "wordpress-seo-premium:21.8"
    )
    
    for plugin_info in "${required_plugins[@]}"; do
        local plugin_name="${plugin_info%:*}"
        local expected_version="${plugin_info#*:}"
        
        if [ -d "wp-content/plugins/$plugin_name" ]; then
            # Try to get the actual version from the plugin
            local actual_version=""
            if [ -f "wp-content/plugins/$plugin_name/$plugin_name.php" ]; then
                actual_version=$(grep -o "Version: [0-9.]*" "wp-content/plugins/$plugin_name/$plugin_name.php" | cut -d' ' -f2)
            elif [ -f "wp-content/plugins/$plugin_name/readme.txt" ]; then
                actual_version=$(grep -o "Stable tag: [0-9.]*" "wp-content/plugins/$plugin_name/readme.txt" | cut -d' ' -f3)
            fi
            
            if [ -n "$actual_version" ]; then
                if [ "$actual_version" = "$expected_version" ]; then
                    log_success "$plugin_name: Version $actual_version ✓"
                else
                    log_error "$plugin_name: Version $actual_version (expected $expected_version) - deployment failed"
                    log_info "Troubleshooting: Check Composer installation and version constraints"
                    all_available=false
                fi
            else
                log_error "$plugin_name: Available but version unknown - deployment failed"
                log_info "Troubleshooting: Check plugin file structure and version headers"
                all_available=false
            fi
        else
            log_error "$plugin_name plugin missing after installation - deployment failed"
            log_info "Troubleshooting: Check Composer installation logs and disk space"
            all_available=false
        fi
    done
    
    if [ "$all_available" = true ]; then
        log_success "All required plugins are available with correct versions"
    else
        exit 1
    fi
    
    echo ""
}

# Main plugin deployment function
deploy_plugins() {
    log_phase "4" "Plugin dependency management"
    
    # Check Composer availability
    if ! check_composer; then
        exit 1
    fi
    
    # Analyze current state
    local plugins_need_update
    plugins_need_update=$(analyze_plugin_state)
    
    # Install dependencies
    install_dependencies "$plugins_need_update"
    
    # List plugins
    list_plugins
    
    # Verify availability
    verify_plugins
}

# Run plugin deployment if script is executed directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    log_info "Starting plugin deployment"
    deploy_plugins
    log_success "Plugin deployment completed successfully!"
fi
