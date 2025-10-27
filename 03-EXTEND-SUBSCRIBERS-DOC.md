# Subscriber API Extension Tutorial

**Document Version**: 1.0.0  
**Date**: October 21, 2025  
**Status**: 📋 Step-by-Step Implementation Guide  

## 🎯 **What You'll Build**

This tutorial walks you through extending your existing **sendy-api-extension** repository to add comprehensive subscriber data endpoints. By the end, your WordPress plugin will have access to detailed subscriber information, engagement metrics, and activity data without needing direct database access.

**Estimated Time**: 20-24 hours for a senior developer (2.5-3 working days)

## 🏗️ **Architecture Overview**

### **Current State** ✅
- **Repository**: [djhanus/sendy-api-extension](https://github.com/djhanus/sendy-api-extension)
- **Existing Endpoints**: Campaign performance APIs (`/campaigns/`, `/reporting/`)
- **WordPress Integration**: Basic Sendy API connection established
- **Authentication**: Uses Sendy's built-in `verify_api_key()` system

### **Proposed Extension** 🆕
```
sendy-app/api/
├── campaigns/          # ✅ Already exists
│   ├── summary.php
│   ├── clicks.php      
│   └── opens.php       
├── reporting/          # ✅ Already exists
│   └── query.php       
└── subscribers/        # 🆕 NEW - Subscriber data endpoints
    ├── detailed.php    # Full subscriber list with engagement
    ├── most-active.php # Top engaged subscribers
    ├── by-campaign.php # Campaign-specific subscriber activity
    └── engagement.php  # Individual subscriber engagement history
```

---

## 📊 **Database Schema Reference**

Based on analysis of the Sendy application structure:

### **Key Tables & Relationships**
```sql
-- Subscribers table (primary data)
subscribers {
    id, name, email, list, custom_fields, 
    timestamp, unsubscribed, bounced, complaint, confirmed,
    last_campaign, ip, country, referrer, gdpr
}

-- Lists table (subscriber groupings)
lists {
    id, app, name, opt_in, custom_fields
}

-- Campaigns table (email campaigns)
campaigns {
    id, app, title, from_name, from_email, 
    sent, opens, clicks, to_send_lists
}

-- Links table (click tracking)
links {
    id, campaign_id, link, clicks
}

-- Apps table (brands/organizations)
apps {
    id, app_name, from_email, from_name
}
```

### **Data Relationships**
- **Subscribers** belong to **Lists** (`subscribers.list = lists.id`)
- **Lists** belong to **Apps/Brands** (`lists.app = apps.id`)
- **Campaigns** belong to **Apps** (`campaigns.app = apps.id`)
- **Opens tracking**: Stored as CSV in `campaigns.opens` field
- **Clicks tracking**: Stored as CSV in `links.clicks` field

---

## 🛠️ **Implementation Plan**

### **Phase 1: Core Subscriber Endpoints** 🎯

#### **1.1 `/api/subscribers/detailed.php`**
**Purpose**: Get comprehensive subscriber data with engagement metrics

**Parameters:**
- `api_key` (required) - Sendy API key
- `brand_id` (required) - Brand/App ID
- `limit` (optional, default: 100) - Number of results
- `offset` (optional, default: 0) - Pagination offset
- `list_id` (optional) - Filter by specific list

**Response Format:**
```json
{
  "status": "success",
  "total_count": 1250,
  "subscribers": [
    {
      "id": 12345,
      "name": "John Smith",
      "email": "john.smith@company.com",
      "signup_date": "2024-03-15",
      "list_name": "SEC Filings",
      "list_id": "encrypted_list_id",
      "total_opens": 23,
      "total_clicks": 8,
      "campaigns_received": 15,
      "engagement_score": 39,
      "country": "US",
      "status": "active",
      "custom_fields": {
        "company": "ABC Corp",
        "title": "CFO"
      }
    }
  ]
}
```

**Key Features:**
- ✅ Paginated results for performance
- ✅ Engagement metrics calculated from campaign data
- ✅ Country and custom field support
- ✅ List filtering capability

#### **1.2 `/api/subscribers/most-active.php`**
**Purpose**: Identify most engaged subscribers by activity

**Parameters:**
- `api_key` (required) - Sendy API key
- `brand_id` (required) - Brand/App ID
- `limit` (optional, default: 10) - Number of top subscribers
- `time_period` (optional, default: 30) - Days to analyze
- `metric` (optional, default: 'combined') - Ranking metric: 'opens', 'clicks', 'combined'

**Response Format:**
```json
{
  "status": "success",
  "analysis_period": "30 days",
  "ranking_metric": "combined",
  "most_active": [
    {
      "id": 12345,
      "name": "John Smith", 
      "email": "john.smith@company.com",
      "list_name": "SEC Filings",
      "recent_opens": 12,
      "recent_clicks": 5,
      "engagement_score": 22,
      "campaigns_in_period": 8,
      "open_rate": 75.0,
      "click_rate": 31.25
    }
  ]
}
```

#### **1.3 `/api/subscribers/by-campaign.php`**
**Purpose**: Get subscribers who interacted with specific campaigns

**Parameters:**
- `api_key` (required) - Sendy API key
- `campaign_id` (required) - Campaign ID
- `action` (required) - 'opened', 'clicked', or 'both'
- `limit` (optional, default: 100) - Number of results

**Response Format:**
```json
{
  "status": "success", 
  "campaign_id": 9575,
  "campaign_title": "SEC Filing: 10-K Report",
  "action_filter": "opened",
  "subscribers": [
    {
      "id": 12345,
      "name": "John Smith",
      "email": "john.smith@company.com",
      "list_name": "SEC Filings",
      "interaction_date": "2024-03-15 14:30:22",
      "country": "US",
      "clicks_in_campaign": 2
    }
  ]
}
```

### **Phase 2: Advanced Analytics** 🚀

#### **2.1 `/api/subscribers/engagement.php`**
**Purpose**: Individual subscriber engagement history

**Parameters:**
- `api_key` (required) - Sendy API key
- `subscriber_id` (required) - Subscriber ID
- `limit` (optional, default: 50) - Campaign history limit

**Response Format:**
```json
{
  "status": "success",
  "subscriber": {
    "id": 12345,
    "name": "John Smith",
    "email": "john.smith@company.com",
    "signup_date": "2024-01-15",
    "total_campaigns": 25,
    "total_opens": 18,
    "total_clicks": 7,
    "engagement_trend": "increasing"
  },
  "campaign_history": [
    {
      "campaign_id": 9575,
      "campaign_title": "SEC Filing: 10-K",
      "sent_date": "2024-03-15",
      "opened": true,
      "clicked": true,
      "click_count": 2
    }
  ]
}
```

#### **2.2 `/api/subscribers/segments.php`**
**Purpose**: Segment subscribers by engagement patterns

**Parameters:**
- `api_key` (required) - Sendy API key  
- `brand_id` (required) - Brand/App ID
- `segment_type` (required) - 'high_engagement', 'low_engagement', 'recent_joiners', 'at_risk'

---

## 💻 **Hands-On Implementation**

### **Step-by-Step Code Creation**

#### **Step 1: Create Your First Endpoint (`detailed.php`)**
Start by copying the structure from your existing `campaigns/summary.php`:

```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'error_log.txt');

include('../_connect.php');
include('../../includes/helpers/short.php');

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/New_York');

//-------------------------- ERRORS -------------------------//
$error_core = array('No data passed', 'API key not passed', 'Invalid API key');
$error_passed = array('Brand ID not passed', 'Subscriber data not found');

//--------------------------- POST --------------------------//
// Parameter handling with validation
if(isset($_POST['api_key'])) $api_key = $_POST['api_key'];
else $api_key = null;

if(isset($_POST['brand_id']) && is_numeric($_POST['brand_id']))
    $brand_id = (int)$_POST['brand_id'];
else $brand_id = null;

//----------------------- VERIFICATION ----------------------//
if($api_key==null) {
    echo $error_core[1];
    exit;
}
else if(!verify_api_key($api_key)) {
    echo $error_core[2];
    exit;
}

if($brand_id==null) {
    echo $error_passed[0];
    exit;
}

//--------------------------- QUERY -------------------------//
// Prepared statements for security
$stmt = $mysqli->prepare('
    SELECT s.id, s.name, s.email, s.timestamp, 
           l.name as list_name, l.id as list_id
    FROM subscribers s
    JOIN lists l ON s.list = l.id  
    WHERE l.app = ? AND s.confirmed = 1 AND s.unsubscribed = 0
    ORDER BY s.timestamp DESC
    LIMIT 100
');

$stmt->bind_param('i', $brand_id);
$stmt->execute();
$result = $stmt->get_result();

// Process results and return JSON
$subscribers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $subscribers[] = [
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'signup_date' => date('Y-m-d', $row['timestamp']),
        'list_name' => $row['list_name']
    ];
}

echo json_encode([
    'status' => 'success',
    'subscribers' => $subscribers
], JSON_PRETTY_PRINT);
?>
```

#### **Step 2: Test Your Endpoint**
Before moving to WordPress, test directly:
```bash
curl -X POST http://your-sendy-url/api/subscribers/detailed.php \
  -d "api_key=YOUR_API_KEY" \
  -d "brand_id=1" \
  -d "limit=5"
```

#### **Step 3: WordPress Integration**

**A. Extend Your Sendy API Class:**
Open `includes/class-sendy-api.php` and add these methods:
```php
class Sendy_Enhanced_API extends Sendy_API {
    
    /**
     * Get detailed subscriber data with engagement metrics
     */
    public function get_detailed_subscribers($brand_id, $options = []) {
        $params = array_merge([
            'brand_id' => $brand_id,
            'limit' => 100,
            'offset' => 0
        ], $options);
        
        return $this->sendy_request('api/subscribers/detailed.php', $params);
    }
    
    /**
     * Get most active subscribers
     */
    public function get_most_active_subscribers($brand_id, $limit = 10, $days = 30) {
        return $this->sendy_request('api/subscribers/most-active.php', [
            'brand_id' => $brand_id,
            'limit' => $limit,
            'time_period' => $days
        ]);
    }
    
    /**
     * Get subscribers who interacted with a campaign
     */
    public function get_campaign_subscribers($campaign_id, $action = 'opened') {
        return $this->sendy_request('api/subscribers/by-campaign.php', [
            'campaign_id' => $campaign_id,
            'action' => $action
        ]);
    }
    
    /**
     * Get individual subscriber engagement history
     */
    public function get_subscriber_engagement($subscriber_id) {
        return $this->sendy_request('api/subscribers/engagement.php', [
            'subscriber_id' => $subscriber_id
        ]);
    }
}
```

#### **Step 4: Add WordPress AJAX Handlers**
Open `includes/class-investor-insight.php` and add these methods:

```php
// In class-investor-insight.php
public function ajax_get_detailed_subscribers() {
    // Verify nonce and permissions
    if (!wp_verify_nonce($_POST['nonce'], 'investor_insight_nonce')) {
        wp_die('Security check failed');
    }
    
    $sendy_api = new Sendy_Enhanced_API();
    $brand_id = get_option('investor_insight_sendy_brand_id');
    
    $result = $sendy_api->get_detailed_subscribers($brand_id, [
        'limit' => $_POST['limit'] ?? 100,
        'offset' => $_POST['offset'] ?? 0
    ]);
    
    wp_send_json_success($result);
}

public function ajax_get_most_active_subscribers() {
    // Similar implementation for most active subscribers
    $sendy_api = new Sendy_Enhanced_API();
    $result = $sendy_api->get_most_active_subscribers(
        get_option('investor_insight_sendy_brand_id'),
        $_POST['limit'] ?? 10,
        $_POST['days'] ?? 30
    );
    
    wp_send_json_success($result);
}
```

Don't forget to register the AJAX actions in your constructor:
```php
// Add these to class-investor-insight.php constructor
add_action('wp_ajax_get_detailed_subscribers', array($this, 'ajax_get_detailed_subscribers'));
add_action('wp_ajax_get_most_active_subscribers', array($this, 'ajax_get_most_active_subscribers'));
```

---

## 🔄 **Frontend Integration Tutorial**

#### **Step 5: Update Subscribers Page JavaScript**
Modify `assets/js/subscribers.js` to use your new API endpoints:

```javascript
// Replace the CSV loading function with this:
async function loadSubscribersFromSendy() {
    try {
        const response = await fetch(ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'get_detailed_subscribers',
                nonce: investorInsight.nonce,
                limit: 100,
                offset: 0
            })
        });
        
        const data = await response.json();
        if (data.success) {
            window.subscribers = data.data.subscribers;
            renderSubscribers();
            updateAllCounts();
        }
    } catch (error) {
        console.error('Failed to load subscribers:', error);
        // Fallback to existing CSV method
        loadCSVFiles();
    }
}
```

#### **Step 6: Add Dashboard Widget**
Create a new "Most Active Subscribers" section in your dashboard:
```javascript
// Load most active subscribers for dashboard
async function loadMostActiveSubscribers() {
    const response = await fetch(ajaxurl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'get_most_active_subscribers',
            nonce: investorInsight.nonce,
            limit: 5,
            days: 30
        })
    });
    
    const data = await response.json();
    if (data.success) {
        renderMostActiveSubscribers(data.data.most_active);
    }
}

function renderMostActiveSubscribers(subscribers) {
    const container = document.getElementById('most-active-container');
    container.innerHTML = subscribers.map(sub => `
        <div class="subscriber-card">
            <div class="subscriber-name">${sub.name}</div>
            <div class="subscriber-email">${sub.email}</div>
            <div class="engagement-score">${sub.engagement_score}</div>
            <div class="activity-stats">
                ${sub.recent_opens} opens, ${sub.recent_clicks} clicks
            </div>
        </div>
    `).join('');
}
```

---

## 📈 **Benefits & Advantages**

### **✅ Architectural Benefits**
- **API-Consistent**: Follows existing Sendy API patterns
- **Update-Safe**: Extensions won't be affected by Sendy updates  
- **Secure**: Uses Sendy's built-in authentication system
- **Scalable**: Easy to add more endpoints as needed
- **Maintainable**: Clean separation from core Sendy files

### **✅ Functional Benefits**
- **Real-time Data**: No more CSV file dependencies
- **Rich Engagement Data**: Opens, clicks, engagement scoring
- **Advanced Analytics**: Subscriber behavior insights
- **Campaign Attribution**: Link subscribers to specific campaigns
- **Performance Optimized**: Paginated results, prepared statements

### **✅ User Experience Benefits**
- **Detailed Subscriber Profiles** with engagement history
- **Most Active Subscribers** identification
- **Campaign Performance Analysis** with subscriber-level data
- **Advanced Filtering** by engagement metrics
- **Real-time Updates** without CSV regeneration

---

## 🚀 **Step-by-Step Implementation Guide**

### **Day 1: Foundation Setup** (6-8 hours)

#### **Step 1.1: Prepare Your Environment** (30 mins)
- [ ] **Clone/backup** your sendy-api-extension repo
- [ ] **Verify access** to Sendy installation directory
- [ ] **Test existing endpoints** (campaigns/summary.php) to ensure setup works
- [ ] **Document your brand_id** and api_key for testing

#### **Step 1.2: Create Directory Structure** (15 mins)
```bash
# Navigate to your Sendy installation
cd /path/to/sendy/api/

# Create new subscribers directory
mkdir subscribers
cd subscribers
```

#### **Step 1.3: Build First Endpoint** (4-5 hours)
- [ ] **Copy template** from existing `campaigns/summary.php`
- [ ] **Create `detailed.php`** following the pattern below
- [ ] **Test with Postman/curl** to verify basic functionality
- [ ] **Debug SQL queries** using error logging

#### **Step 1.4: WordPress Integration Setup** (1.5-2 hours)
- [ ] **Create backup** of your WordPress plugin
- [ ] **Add new methods** to Sendy_API class
- [ ] **Create test AJAX endpoint** for basic subscriber data
- [ ] **Test WordPress → Sendy API connection**

### **Day 2: Core Endpoints** (7-8 hours)

#### **Step 2.1: Most Active Subscribers** (3-4 hours)
- [ ] **Copy `detailed.php`** as template for `most-active.php`
- [ ] **Implement engagement scoring** SQL (see queries below)
- [ ] **Add time period filtering** (30, 60, 90 days)
- [ ] **Test with different brand_ids** and time periods

#### **Step 2.2: Campaign Subscribers** (2-3 hours)
- [ ] **Create `by-campaign.php`** endpoint
- [ ] **Parse opens/clicks CSV fields** (FIND_IN_SET queries)
- [ ] **Add action filtering** (opened/clicked/both)
- [ ] **Test with real campaign data**

#### **Step 2.3: WordPress AJAX Handlers** (1.5-2 hours)
- [ ] **Add AJAX actions** to class-investor-insight.php
- [ ] **Create nonce verification**
- [ ] **Add error handling** for API failures
- [ ] **Test via browser console**

### **Day 3: UI Integration** (6-8 hours)

#### **Step 3.1: Update Subscribers Page** (3-4 hours)
- [ ] **Modify subscribers.js** to use new API endpoints
- [ ] **Replace CSV data loading** with Sendy API calls
- [ ] **Update table rendering** for new data structure
- [ ] **Test filtering and sorting** with real data

#### **Step 3.2: Dashboard Widget** (2-3 hours)
- [ ] **Add "Most Active" section** to dashboard.php template
- [ ] **Create JavaScript** for loading/rendering active subscribers
- [ ] **Style subscriber cards** with engagement metrics
- [ ] **Add refresh functionality**

#### **Step 3.3: Performance & Polish** (1-2 hours)
- [ ] **Add loading states** and error handling
- [ ] **Implement pagination** for large datasets
- [ ] **Test with various data sizes**
- [ ] **Optimize API response sizes**

### **Optional: Advanced Features** (+1-2 days)
- [ ] **Individual subscriber history** endpoint
- [ ] **Engagement trend analysis**
- [ ] **Subscriber segmentation** by behavior
- [ ] **Export functionality** for subscriber lists

---

## 🔧 **Development Notes**

### **Database Query Patterns**

**Engagement Scoring Algorithm:**
```sql
-- Calculate engagement score: opens + (clicks * 2)
SELECT s.id, s.name, s.email,
       COUNT(DISTINCT c.id) as campaigns_received,
       SUM(CASE WHEN FIND_IN_SET(s.id, c.opens) THEN 1 ELSE 0 END) as total_opens,
       (SELECT COUNT(*) FROM links l 
        JOIN campaigns c2 ON l.campaign_id = c2.id 
        WHERE c2.app = ? AND FIND_IN_SET(s.id, l.clicks)) as total_clicks,
       (SUM(CASE WHEN FIND_IN_SET(s.id, c.opens) THEN 1 ELSE 0 END) + 
        (SELECT COUNT(*) FROM links l 
         JOIN campaigns c2 ON l.campaign_id = c2.id 
         WHERE c2.app = ? AND FIND_IN_SET(s.id, l.clicks)) * 2) as engagement_score
FROM subscribers s
JOIN lists l ON s.list = l.id
LEFT JOIN campaigns c ON l.app = c.app
WHERE l.app = ? AND s.confirmed = 1 AND s.unsubscribed = 0
GROUP BY s.id
ORDER BY engagement_score DESC
```

### **Performance Considerations**
- **Indexing**: Ensure proper database indexes on frequently queried fields
- **Pagination**: Implement LIMIT/OFFSET for large subscriber lists  
- **Caching**: Consider WordPress transient caching for expensive queries
- **Prepared Statements**: All queries use prepared statements for security

### **Error Handling**
- **API Key Validation**: Consistent with existing Sendy API endpoints
- **Parameter Validation**: Type checking and sanitization
- **Database Errors**: Proper MySQL error handling and logging
- **JSON Responses**: Standardized error response format

---

## ⏱️ **Time Estimation Breakdown**

### **For a Senior Developer** (10+ years WordPress, 1-3 years API extensions)

**Day 1 (6-8 hours):**
- Setup and first endpoint: 4-5 hours
- WordPress integration: 1.5-2 hours  
- Testing and debugging: 1-1.5 hours

**Day 2 (7-8 hours):**
- Additional endpoints: 5-6 hours
- AJAX handlers: 1.5-2 hours
- API testing: 30 mins

**Day 3 (6-8 hours):**
- Frontend integration: 4-5 hours
- Dashboard widget: 2-3 hours
- Polish and testing: 1 hour

**Total: 20-24 hours** (2.5-3 working days)

### **Potential Time Savers:**
- ✅ **Existing API extension** saves 4-6 hours
- ✅ **Established WordPress structure** saves 2-3 hours  
- ✅ **Database schema knowledge** saves 1-2 hours

### **Common Time Sinks:**
- ⚠️ **SQL query optimization** can add 2-4 hours
- ⚠️ **CORS/authentication issues** can add 1-2 hours
- ⚠️ **Large dataset performance** can add 2-3 hours

---

## 🛠️ **Troubleshooting Guide**

### **Common Issues & Solutions**

**"API key not passed" error:**
```bash
# Check your POST data format
curl -X POST -d "api_key=xxx&brand_id=1" http://your-url/api/subscribers/detailed.php
```

**Empty subscriber results:**
```sql
-- Test your brand_id directly in database
SELECT COUNT(*) FROM subscribers s 
JOIN lists l ON s.list = l.id 
WHERE l.app = YOUR_BRAND_ID AND s.confirmed = 1;
```

**WordPress AJAX not responding:**
```javascript
// Check browser console for errors
console.log('AJAX URL:', ajaxurl);
console.log('Nonce:', investorInsight.nonce);
```

---

## 📚 **References & Dependencies**

### **Required Files**
- **Sendy API Extension**: [djhanus/sendy-api-extension](https://github.com/djhanus/sendy-api-extension)
- **WordPress Plugin**: Current lifesci-ir-insight-plugin structure
- **Sendy Installation**: v6.1.3+ with MySQL database access

### **Related Documentation**
- [01-SIMPLIFIED-SENDY-INTEGRATION.md](./01-SIMPLIFIED-SENDY-INTEGRATION.md)
- [02-CAMPAIGN-ANALYTICS-API-INTEGRATION.md](./02-CAMPAIGN-ANALYTICS-API-INTEGRATION.md)
- [Sendy Official API Documentation](https://sendy.co/api)

### **Testing & Validation**
- **API Testing**: Postman collection for endpoint validation
- **WordPress Testing**: WP-CLI and browser testing
- **Performance Testing**: Database query optimization
- **Security Testing**: API authentication and SQL injection prevention

---

**Next Steps**: Review implementation plan and begin Phase 1 development with `/api/subscribers/detailed.php` endpoint creation.
