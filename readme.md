# Sendy API Addons

## Query

[query.php](query.php) is a robust api that allows querying of campaigns based on name/label. 

Using the query tag you can search for campaigns that contain the query in the name/label.

It provides a detailed report consisting of total recepients, clicks, rates, and more. 

See [query.php#26](query.php#26) for detailed information about the query fields and fields returned.

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/M4M314FOFQ)

### Instructions and Docs

Put this file in a new folder within the /api/ folder, called "reporting", and call it "query.php".

> Call by POST to api/reporting/query.php with the following elements

  `api_key` (your API key)

  `brand_id`  (the brand ID you want to search)

  `query` (optional)  Search within the campaign name/label. If not included all campaigns will be returned.

  `order` (optional) sort by date sent 'asc' or 'desc' (default is 'desc')

  `sent` (optional) filter by date sent. Can be a Unix timestamp or a date in M/d/YY format. If not included all campaigns will be returned.


> The data return is in JSON and contains following:

`brand_id` the brand ID you sent

`id` the campaign ID

`label` the campaign label/name

`date_sent` the date the campaign was sent converted from Unix

`total_sent` the total sent for this campaign

`total_opens` the total opens figure, visible in your dashboard

`open_rate` total opens as a percentage of total sent

`unique_opens` de-duplicated opens figure

`open_percentage` the percentage of unique opens against total sent

`total_clicks` the total number of clicks on all links in the campaign

`click_rate` the total clicks as a percentage of total sent

`links` an array of links within the campaign, with the following elements:

  `url` the URL of the link

  `clicks` the number of clicks on the link

## Links and Reports
[links.php](links.php) and [reports.php](reports.php) were forked from [jamescridland/links.php](https://gist.github.com/jamescridland/4a5e013c5d5edbcd99ded61412a16568) and [jamescridland/reports.php](https://gist.github.com/jamescridland/1f4ea72fbd262fa31850ccfd5a54df0a) and updated in 2024 for Sendy v6.1.1

They enable you to pull campaign specific data utilizing an API call that specifies a campaign by exact title/label. 

