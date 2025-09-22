# sendy-api-utility Integration Instructions

## 🎯 Core Integration (Required)

Your sendy-api-extension now provides the missing campaign endpoints that your utility was expecting!

### Required Upload
Upload **only** the `/campaigns/` folder to your Sendy installation:

```
your-sendy-install/api/campaigns/
├── summary.php     ✅ Required  
├── clicks.php      ✅ Required
└── opens.php       ✅ Required
```

**⚠️ Note:** The `/reporting/` folder is optional and NOT needed for your utility to work!

### 1. Update your sendy-api-utility/index.php

Uncomment these lines around line 55-57:

```php
<!-- <button type="submit" name="action" value="get_campaigns">Get Campaigns</button> -->
<button type="submit" name="action" value="campaign_summary">Campaign Summary</button>
<button type="submit" name="action" value="campaign_clicks">Campaign Clicks</button>
```

Change to:

```php
<!-- <button type="submit" name="action" value="get_campaigns">Get Campaigns</button> -->
<button type="submit" name="action" value="campaign_summary">Campaign Summary</button> 
<button type="submit" name="action" value="campaign_clicks">Campaign Clicks</button>
```

### 2. Add the missing campaign_opens button (optional)

Add this line with the other buttons:

```php
<button type="submit" name="action" value="campaign_opens">Campaign Opens</button>
```

### 3. Add the case handler for campaign_opens

Around line 115, add this case to the switch statement:

```php
case 'campaign_opens':
    if(!$campaign_id) {
        echo "<p class='error'>Campaign ID required for this test</p>";
        break;
    }
    $result = sendy_request($api_url . '/api/campaigns/opens.php', [
        'api_key' => $api_key,
        'campaign_id' => $campaign_id
    ]);
    break;
```

### 4. Test the Integration

1. Make sure your sendy-api-extension files are installed in your Sendy installation
2. Run your sendy-api-utility locally: `php -S localhost:8080`
3. Open http://localhost:8080 in your browser
4. Fill in your Sendy URL, API key, and a campaign ID
5. Test the new campaign buttons!

### Expected Results

- **Campaign Summary**: Should return format like "1250,45,12,8"
- **Campaign Clicks**: Should return JSON array of links and click counts
- **Campaign Opens**: Should return detailed open data with country breakdown

### Troubleshooting

If you get errors:

1. **"Campaign ID required"**: Make sure you filled in the Campaign ID field
2. **API errors**: Verify your API key and Sendy URL are correct  
3. **404 errors**: Ensure the sendy-api-extension files are uploaded to the correct paths in your Sendy installation

### Alternative: Use Label-Based Access

Instead of campaign_id, you can also use the legacy approach with brand_id + label by modifying the utility to send those parameters instead.

The new endpoints support both approaches for maximum flexibility!