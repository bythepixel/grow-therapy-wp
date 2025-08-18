## Infrastructure
- **Hosting**: Kinsta
- **CMS**: WordPress

## Local Development

### Prerequisites
- **Docker** - For containerized local environment

### Setup Instructions

> **Note**: This is a work-in-progress and may change as we build out the new site.

1. **Start Local Environment**
   ```bash
   docker-compose up -d
   ```

2. **Access WordPress**
   - Visit [http://localhost:8000](http://localhost:8000)
   - Complete initial WordPress setup

3. **Install WP Migrate DB Pro**
   - Check 1Password for plugin credentials and installation file
   - Install and activate the plugin

4. **Sync Database from Dev**
   - Use WP Migrate DB Pro to pull database from `Dev-Feature` Kinsta server
   - This ensures your local environment matches the latest development state
   - All plugins and their configurations will be synced automatically

5. **Configure Plugin Licenses**
   - Plugin licenses and credentials available in this [Google Doc](https://docs.google.com/document/d/1XPgaV8K26F3jLI_km0CU2TrEo91ouPvT5nHhD7y-Mo8/edit?usp=sharing)
   - Activate licenses for premium plugins as needed
   - **Note**: All plugins are pulled from the dev environment, no local Composer installation required

## Deployment Workflow & Environments

This repository uses GitHub Actions for automated deployment to different environments in Kinsta. The workflow ensures that new features and content are reviewed, tested, and approved before going live, while giving both teams the ability to work safely without overwriting each other's changes.

### Environments & Workflows

#### 🔧 Dev (Feature Testing)
- **Purpose**: For developers to test new features, plugins, and theme changes
- **Git Branch**: `dev`
- **GitHub Environment**: Dev (Feature-testing)
- **Kinsta Environment**: Dev-Feature
- **Workflow File**: `.github/workflows/deploy-dev.yml`
- **Deployment**: Automatic on commit to `dev` branch
- **Use Case**: Developers can merge multiple feature branches into `dev` for combined QA and client review

#### 🧪 Dev (Sandbox)
- **Purpose**: A dedicated testing environment for the Grow team to test plugins, ACFs, and other experimental updates
- **Git Branch**: `dev-sandbox`
- **GitHub Environment**: Dev (Sandbox)
- **Kinsta Environment**: Dev-Sandbox
- **Workflow File**: `.github/workflows/deploy-dev-sandbox.yml`
- **Deployment**: Automatic on commit to `dev-sandbox` branch
- **Use Case**: Separate environment for Grow team without impacting main workflows

#### 🚀 Staging (Pre-Production)
- **Purpose**: A copy of the live site used for quality assurance (QA) and client review before release
- **Git Branch**: `staging`
- **GitHub Environment**: Staging (Pre-production)
- **Kinsta Environment**: Pre-prod
- **Workflow File**: `.github/workflows/deploy-staging.yml`
- **Deployment**: Automatic on commit to `staging` branch

#### 🌟 Production (Live Site)
- **Purpose**: The official live website
- **Git Branch**: `main`
- **GitHub Environment**: Production (Live)
- **Kinsta Environment**: Live
- **Workflow File**: `.github/workflows/deploy-prod.yml`
- **Deployment**: Automatic on commit to `main` branch
- **Content Management**: Primarily managed by the marketing team (Kyle, Sindu, and Shelby)
  - **Safety Features**: Uses Revisions plugin for content creation, preview, and publication

### Workflow Process

1. **Development Phase**
   - Features developed and tested in `dev` environment
   - Multiple feature branches can be merged for combined testing
   - Once features pass QA and client review, they're merged into `staging`

2. **Staging Phase**
   - Final testing and verification in staging environment
   - ACFs, plugins, and Bricks templates are validated
   - After approval, changes are merged into `main`

3. **Production Phase**
   - Automatic deployment to live site
   - Content changes managed via Revisions plugin
   - Version history and easy rollbacks available

4. **Grow Testing**
   - Separate environment for the Grow team to experiment in
   - Updated regularly to stay in sync with production
   - Coordination required to prevent overwriting experimental work by the Grow team

### Required Secrets

The workflows require the following GitHub repository secrets to be configured, these are configured in their respective environments. These values can be found in the Kinsta dashboards:

- `KINSTA_SERVER_IP`: IP address of the Kinsta server
- `KINSTA_USERNAME`: SSH username for server access
- `KINSTA_PASSWORD`: SSH password for server authentication
- `KINSTA_PORT`: SSH port number

## Plugin Management & Versioning

This repository uses a hybrid approach to manage WordPress plugins, combining Composer dependency management with Git tracking for optimal efficiency and control.

### Plugin Categories

#### 🆓 Composer-Managed Plugins (11 plugins)
These free plugins are automatically installed and updated via Composer with exact version pinning:

- **Advanced Custom Fields**
- **Easy Table of Contents**
- **Enable Media Replace**
- **Redirection**
- **SEO by Rank Math**
- **Simple Page Ordering**
- **User Role Editor**
- **WP GraphQL**
- **WPGraphQL ACF**
- **WPGraphQL Smart Cache**
- **WordPress SEO**

#### 💎 Premium Plugins (25 plugins)
These premium/paid plugins are tracked in Git and require manual installation:

- **Admin Columns Pro** - Advanced table management
- **Advanced Custom Fields Pro** - Enhanced custom fields
- **Gravity Forms Suite** - Form management and add-ons
- **WP All Import/Export Pro** - Data import/export tools
- **Rank Math Pro** - Advanced SEO features
- **Yoast SEO Premium** - Enhanced SEO capabilities
- **WP Migrate DB Pro** - Database migration tools
- **Performance & Security** - Various optimization plugins

### Benefits

✅ **Minimal Repository Bloat** - Only premium plugins in Git  
✅ **Exact Version Control** - Free plugins pinned to specific versions  
✅ **Automatic Updates** - Composer handles dependency resolution  
✅ **Deployment Safety** - Fail-fast strategy with comprehensive verification  
✅ **Environment Consistency** - Same plugin versions across all environments  
✅ **Easy Maintenance** - Single source of truth for plugin requirements
