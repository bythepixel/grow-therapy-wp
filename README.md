## Infrastructure
- **Hosting**: Kinsta

## GitHub Workflows

This repository uses GitHub Actions for automated deployment to different environments in Kinsta. Each workflow automatically deploys code when changes are pushed to specific branches.

### 🚀 Deploy to Production
- **File**: `.github/workflows/deploy-prod.yml`
- **Trigger**: Push to `main` branch
- **GitHub Environment**: Production (Live)
- **Kinsta Environment**: Live
- **Purpose**: Deploys stable, tested code to the live production environment

### 🧪 Deploy to Staging
- **File**: `.github/workflows/deploy-staging.yml`
- **Trigger**: Push to `staging` branch
- **GitHub Environment**: Staging (Pre-production)
- **Kinsta Environment**: Pre-prod
- **Purpose**: Deploys code for final testing before production release

### 🔧 Deploy to Dev for Feature Testing
- **File**: `.github/workflows/deploy-dev.yml`
- **Trigger**: Push to `dev` branch
- **GitHub Environment**: Dev (Feature-testing)
- **Kinsta Environment**: Dev-Feature
- **Purpose**: Deploys code for feature testing and integration

### 🏖️ Deploy to Dev Sandbox
- **File**: `.github/workflows/deploy-dev-sandbox.yml`
- **Trigger**: Push to `dev-sandbox` branch
- **GitHub Environment**: Dev (Sandbox)
- **Kinsta Environment**: Dev-Sandbox
- **Purpose**: Deploys experimental code for isolated testing

### Required Secrets

The workflows require the following GitHub repository secrets to be configured, these are configured in their respective environments. These values can be found in the Kinsta dashboards:

- `KINSTA_SERVER_IP`: IP address of the Kinsta server
- `KINSTA_USERNAME`: SSH username for server access
- `KINSTA_PASSWORD`: SSH password for server authentication
- `KINSTA_PORT`: SSH port number
