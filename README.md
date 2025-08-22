# Grow Therapy WordPress Site

## 🚀 Quick Start
- **Local Setup**: `docker-compose up -d` → http://localhost:8000
- **Database**: Sync from Pre-prod using WP Migrate DB Pro

## 🏗️ Architecture
- **Hosting**: Kinsta
- **CMS**: WordPress + Bricks Builder
- **Deployment**: GitHub Actions → Kinsta environments

## 🔄 Development Workflow
> **Note**: This is a work-in-progress and may change as we build out the new site.

1. **Feature Branch** from `staging` → develop locally
2. **Merge to `dev`** → deploy to live server for QA/client review
3. **Merge to `staging`** → final QA before production
4. **Deploy to `main`** → live site

**Client-Driven Development:**
- **Existing templates & settings** → Edit on production
- **New features & templates** → Develop locally, deploy to staging
- **No conflicts** - Clear separation of concerns

### What Happens on Deploy
- **BricksSync**: JSON files imported to database (templates, colors, typography)
- **Plugin Management**: Composer plugins updated, premium plugins synced
- **Theme Updates**: Custom theme changes deployed
- **Rollback Protection**: Automatic rollback if deployment fails

## 🌍 Environments
- **Dev**: Feature testing & client review
- **Dev-Sandbox**: Client content/plugin work  
- **Staging**: Final QA before release
- **Production**: Live site

## 🎨 Bricks Builder
- **Local**: Auto-updates JSON files when making changes
- **Version Control**: All settings tracked in Git
- **Client-Driven Workflow**: Global settings, existing components, and template modifications should be made on production to avoid overwriting local development work

## 📦 Plugin Management
- **Composer**: 11 free plugins (version controlled)
- **Git**: 25 premium plugins (manual install)
- **All plugins version controlled** for consistency across environments

## 🔧 Required Secrets
Configure in GitHub Environments:
- `KINSTA_SERVER_IP`, `KINSTA_USERNAME`, `KINSTA_PASSWORD`, `KINSTA_PORT`

> **📚 For detailed deployment information, see [`.github/workflows/README.md`](.github/workflows/README.md)**

---

## 🚀 Quick Start Details

### Prerequisites
- **Docker** - For containerized local environment

### Setup Steps
1. **Start Local Environment**
   ```bash
   docker-compose up -d
   ```

2. **Access WordPress**
   - Visit [http://localhost:8000](http://localhost:8000)
   - Complete initial WordPress setup



3. **Install WP Migrate DB Pro**
   - License available in this [Google Doc](https://docs.google.com/document/d/1XPgaV8K26F3jLI_km0CU2TrEo91ouPvT5nHhD7y-Mo8/edit?usp=sharing)
   - Install and activate the plugin

4. **Sync Database from Pre-prod**
   - Use WP Migrate DB Pro to pull database from `Pre-prod` Kinsta server
   - This ensures your local environment matches the latest working state

5. **BricksSync Setup**
   > **Note**: BricksSync automatically updates JSON files when you make changes locally. No setup required!

## 🔄 Workflow Details

### Development Process
1. **Feature Development**
   - Create feature branch from `staging` (default branch)
   - Develop and test locally
   - Merge to `dev` for live testing and client review

2. **QA & Review**
   - Multiple features can be merged to `dev` for combined testing
   - Client reviews on live `dev` server
   - Once approved, merge to `staging` for final QA

3. **Production Release**
   - Final testing in `staging` environment
   - Deploy `staging` → `main` (production)
   - Live site updated

### Environment Purposes
- **`dev`**: Live feature testing and client review (prevents blocking between developers)
- **`dev-sandbox`**: Client content/plugin work (separate from main workflow)
- **`staging`**: Final QA before production release
- **`main`**: Live production site

### BricksSync Integration
- **Local Auto-update**: JSON files automatically updated when making changes
- **Production Sync**: Use `./scripts/sync-production-brickssync.sh` to get latest client changes
- **Version Control**: All Bricks settings tracked in Git

## 🔧 Deployment Secrets

All workflows require these secrets configured in their respective GitHub environments:

- `KINSTA_SERVER_IP`: IP address of the Kinsta server
- `KINSTA_USERNAME`: SSH username for server access
- `KINSTA_PASSWORD`: SSH password for server authentication
- `KINSTA_PORT`: SSH port number

Find these values in your Kinsta dashboard.
