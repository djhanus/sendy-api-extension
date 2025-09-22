# Sendy API Extensions

## Overview

This extension provides **campaign reporting APIs** that fill the gap in Sendy's official API, which currently lacks campaign performance endpoints.

### 🎯 Core Campaign APIs (Required)

**Location:** `/api/campaigns/` - These are the essential endpoints that work with [sendy-api-utility](https://github.com/djhanus/sendy-api-utility)

#### `/api/campaigns/summary.php` ⭐
Returns campaign performance summary in the format expected by testing utilities: `"sent,opens,clicks,unsubscribes"`

**Parameters:**
- `api_key` (required) - Your API key
- `campaign_id` (optional) - Direct campaign ID access  
- `brand_id` + `label` (optional) - Legacy label-based access

**Example Response:**
```
1250,45,12,8
```

#### `/api/campaigns/clicks.php` ⭐ 
Returns detailed click tracking data per link

**Example Response:**
```json
[
  {
    "url": "https://example.com/link1", 
    "clicks": 25
  },
  {
    "url": "https://example.com/link2",
    "clicks": 10  
  }
]
```

#### `/api/campaigns/opens.php` ⭐
Returns detailed open tracking data including country breakdown

**Example Response:**
```json
{
  "total_opens": 361,
  "unique_opens": 208,
  "country_opens": {
    "US": 127,
    "GB": 57,
    "AU": 48
  },
  "total_sent": "341", 
  "brand_id": "1",
  "label": "Campaign Name",
  "campaign_id": "123"
}
```

---

### 🔍 Advanced Reporting (Optional)

**Location:** `/api/reporting/` - Power user features for advanced campaign analysis

#### `/api/reporting/query.php` 🚀
**⚠️ OPTIONAL** - This is NOT required for basic campaign API functionality!

Advanced campaign search and bulk reporting with filtering capabilities.

**What it provides:**
- 🔎 **Search campaigns** by name/label pattern matching
- 📅 **Date filtering** - campaigns sent after/before specific dates  
- 📊 **Bulk reporting** - multiple campaigns in one API call
- 🎛️ **Sorting options** - by date sent (asc/desc)
- 📈 **Rich data** - comprehensive JSON with nested link arrays

**Parameters:**
- `api_key` (required) - Your API key
- `brand_id` (optional) - Brand ID to search within
- `campaign_id` (optional) - Direct campaign access  
- `query` (optional) - Search pattern for campaign names
- `date_sent` (optional) - Filter by date sent
- `order` (optional) - Sort by date: 'asc' or 'desc'

**Example Advanced Usage:**
```php
// Find all newsletters from last month
'query' => 'newsletter',
'date_sent' => '2024-01-01'

// Get comprehensive data for campaign ID 123  
'campaign_id' => 123
```

---

## Installation

### Minimal Installation (Recommended)
Upload **only** the `/campaigns/` folder to your Sendy installation:

```
your-sendy-install/api/campaigns/
├── summary.php     ⭐ Required
├── clicks.php      ⭐ Required  
└── opens.php       ⭐ Required
```

### Full Installation (Optional Power Features)
Upload **both** folders:

```
your-sendy-install/api/
├── campaigns/          ⭐ Required for basic functionality
│   ├── summary.php
│   ├── clicks.php      
│   └── opens.php       
└── reporting/          🚀 Optional advanced features
    └── query.php       
```

---

## API Usage Examples

### Simple Campaign Summary (Most Common)
```php
POST /api/campaigns/summary.php
{
  "api_key": "your-key",
  "campaign_id": 123
}
// Returns: "1250,45,12,8"
```

### Advanced Campaign Search (Power Users)
```php  
POST /api/reporting/query.php
{
  "api_key": "your-key",
  "brand_id": 1,
  "query": "newsletter", 
  "date_sent": "2024-01-01"
}
// Returns: Comprehensive JSON with multiple campaigns
```

---

## Compatibility & Requirements

- **Sendy Version:** v6.1.3+ (tested and compatible)
- **PHP:** Compatible with PHP 8.1+ 
- **API Structure:** Follows official Sendy API conventions
- **Backward Compatibility:** Supports both `campaign_id` and legacy `label + brand_id` approaches

### What's New in v2.0

- ✅ Standard `/api/campaigns/` endpoint structure
- ✅ Dual parameter support (campaign_id OR label+brand_id)
- ✅ Simple response formats for utility compatibility  
- ✅ Optional advanced reporting features
- ✅ Clear separation of core vs optional features
- ✅ Fixed total_clicks initialization bug
- ✅ Improved error handling

---

## Quick Start

1. **Download** this repository
2. **Upload** the `campaigns/` folder to `/api/campaigns/` in your Sendy installation
3. **Test** using the included `test_endpoints.php` script
4. **Optional:** Upload `reporting/` folder for advanced features

That's it! Your campaign APIs are now available and compatible with sendy-api-utility. 

