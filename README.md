## Infrastructure
- **Hosting**: Kinsta
- **CMS**: WordPress

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
