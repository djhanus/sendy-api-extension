# Sendy API Extensions

This extension provides **campaign reporting APIs** that fill the gap in Sendy's official API, which currently lacks campaign performance endpoints.

* [https://sendy.co/api](https://sendy.co/api)

## Campaign Summary (Array)

### `/api/campaigns/summary.php`
Returns campaign performance summary in the format expected by testing utilities: `"sent,opens,clicks,unsubscribes"`

**Parameters:**
- `api_key` (required) - Your API key
- `campaign_id` (optional) - Direct campaign ID access  
- `brand_id` + `label` (optional) - Legacy label-based access

**Example Response:**
```
1250,45,12,8
```

## Campaign Summary (JSON)

### `/api/campaigns/opens.php`
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

## Campaign URL Clicks

### `/api/campaigns/clicks.php`
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


## Advanced Reporting (Rich Data)

### `/api/reporting/query.php` 🚀

Advanced campaign search and bulk reporting with filtering capabilities.

<!-- **What it provides:**
- query specific campaign id
- 📅 **Date filtering** - campaigns sent after/before specific dates  
- 📊 **Bulk reporting** - multiple campaigns in one API call
- 🎛️ **Sorting options** - by date sent (asc/desc)
- 📈 **Rich data** - comprehensive JSON with nested link arrays -->

<!-- {
  "campaigns": [
    {
      "brand_id": "1",
      "id": "123",
      "label": "Weekly Newsletter March 2024",
      "date_sent": "Sunday, March 10, 2024 2:30:45 PM",
      "total_sent": 1250,
      "total_opens": 361,
      "open_rate": 28.88,
      "unique_opens": 208,
      "open_percentage": 16.64,
      "total_clicks": 45,
      "click_rate": 3.6,
      "links": [
        {
          "url": "https://newsletter.com/article1",
          "clicks": 25
        },
        {
          "url": "https://newsletter.com/unsubscribe", 
          "clicks": 12
        },
        {
          "url": "https://newsletter.com/social",
          "clicks": 8
        }
      ]
    },
    {
      "brand_id": "1", 
      "id": "124",
      "label": "Product Launch Announcement",
      "date_sent": "Tuesday, March 12, 2024 10:15:22 AM",
      "total_sent": 2100,
      "total_opens": 672,
      "open_rate": 32.0,
      "unique_opens": 445,
      "open_percentage": 21.19,
      "total_clicks": 89,
      "click_rate": 4.24,
      "links": [
        {
          "url": "https://shop.com/new-product",
          "clicks": 67
        },
        {
          "url": "https://shop.com/discount-code",
          "clicks": 22
        }
      ]
    }
  ]
} -->

**Parameters:**
- `query` (optional) - Search pattern for campaign names
- `date_sent` (optional) - Filter by date sent
- `order` (optional) - Sort by date: 'asc' or 'desc'
- comprehensive JSON with nested link arrays


## Installation

Upload the assets as sub-directories in the main `/api/` folder

```
application/api/
├── campaigns/          // Required for basic functionality
│   ├── summary.php
│   ├── clicks.php      
│   └── opens.php       
└── reporting/          // Optional advanced features
    └── query.php       
```

## Compatibility & Requirements

- **Sendy Version:** v6.1.3+
- **PHP:** Compatible with PHP 8.1+ 
- **API Structure:** Follows official Sendy API conventions
