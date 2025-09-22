# Advanced Reporting Extension

**⚠️ OPTIONAL POWER USER FEATURE**

This folder contains advanced campaign reporting capabilities that are **NOT required** for the basic campaign API endpoints to function.

## What's in here?

### `query.php` - Advanced Campaign Search & Bulk Reporting

This provides power user features like:
- 🔎 Campaign search by name pattern
- 📅 Date-based filtering  
- 📊 Bulk campaign analysis
- 🎛️ Advanced sorting options
- 📈 Rich JSON responses with nested data

## Installation Decision

- **Skip this folder** if you only need basic campaign APIs for sendy-api-utility
- **Include this folder** if you want advanced campaign search and bulk reporting features

The `/api/campaigns/` endpoints work perfectly without this optional extension!