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
    
    # Check WPGraphQL
    if [ -d "wp-content/plugins/wp-graphql" ]; then
        log_success "WPGraphQL: Already installed"
    else
        log_warning "WPGraphQL: Not found, will install"
        plugins_need_update=true
    fi
    
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
    log_info "Verifying required plugins are available..."
    
    local all_available=true
    
    # Check WPGraphQL
    if [ -d "wp-content/plugins/wp-graphql" ]; then
        log_success "WPGraphQL: Available for use"
    else
        log_error "WPGraphQL plugin missing after installation - deployment stopped"
        log_info "Troubleshooting: Check Composer installation logs and disk space"
        all_available=false
    fi
    
    if [ "$all_available" = true ]; then
        log_success "All required plugins are available"
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
