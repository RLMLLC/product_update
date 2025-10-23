# PrestaShop Product Hook Trigger Script

## 1. Summary

This is a utility script for PrestaShop 1.7 designed to solve a specific data synchronization problem.

Its purpose is to programmatically "touch" (load and re-save) products in batches. This action forces PrestaShop to execute the `actionProductUpdate` hook for each product, which is essential for third-party modules (like **ShoppingFeed**) to detect and synchronize changes that were made directly in the database.

## 2. The Problem This Solves

When product data is modified directly in the database (e.g., using an external tool like Store Manager, Navision, or running a direct SQL query), PrestaShop's application logic is bypassed.

This means that essential **hooks** are never triggered.

In this specific case, new EAN/GTIN codes were added to thousands of products by modifying the `ean13` column in the `ps_product` table. The ShoppingFeed module, which "listens" for the `actionProductUpdate` hook, was never notified of these changes and therefore did not send the new EANs to the marketplaces.

## 3. The Solution

This script (`trigger_hooks.php`) loads the complete PrestaShop environment. It then fetches a list of products based on specific criteria and performs a `$product->save()` operation on each one.

This `save()` action, even if no new data is being saved, forces PrestaShop to run its full update logic, which **includes triggering the `actionProductUpdate` hook**.

As a result, ShoppingFeed (and any other module listening for this hook) will "see" the product update and add the product to its synchronization queue.

### Script Logic:

* **Loads** the PrestaShop environment.
* **Identifies** a list of products to process.
* **Filters** this list to only include products that are:
    1.  `active` = 1 (Active products)
    2.  `ean13` IS NOT NULL AND `ean13` != "" (Products that have an EAN code)
* **Processes** these products in batches (e.g., 100 at a time) to prevent server timeouts.
* **Executes** `$product->save()` for each product in the batch.
* **Provides** a link to process the next batch.

## 4. Requirements

* PrestaShop 1.7.x
* Server access to upload the PHP file.

## 5. Installation

1.  Locate your unique PrestaShop admin directory. This is the folder in your PrestaShop root that has been renamed for security (e.g., `admin123xyz`, etc.).
2.  Upload the `trigger_hooks.php` file into this directory.

Placing it in the admin directory provides a basic layer of security, as the URL is not easily guessable.

## 6. How to Use

**!!! WARNING: Always create a full database backup before running this script. !!!**

1.  Open your web browser.
2.  Navigate to the script's URL. For example:
    `https://example.com/admin123xyz/trigger_hooks.php`
3.  To start the process, you must provide the `batch` and `page` parameters in the URL.
    * `batch`: The number of products to process per page load. A good starting point is `100`.
    * `page`: The batch number to start with. Always start with `1`.

4.  **Start with the first batch:**
    `https://example.com/admin123xyz/trigger_hooks.php?batch=100&page=1`

5.  The script will run and display a list of the product IDs it has processed.
6.  At the bottom of the page, you will see a link: **"Process Next Batch (Page 2)"**.
7.  Click this link to process the next 100 products.
8.  Repeat this process (clicking the "Next Batch" link) until the script displays the message: **"Process Complete"**.

## 7. Troubleshooting

* **504 Gateway Timeout / Script stops:** This means your server is timing out before it can finish the batch. The most common reason is that the `batch` size is too large.
    * **Solution:** Reduce the batch size. Try again with `?batch=50&page=X` (replacing `X` with the page number you were on).

## 8. 🚨 CRITICAL: Security Warning

This script is a powerful administrative tool. Leaving it on your server after you are finished is a **significant security risk**.

**AFTER YOU HAVE FINISHED THE ENTIRE PROCESS, YOU MUST DELETE THE `trigger_hooks.php` FILE FROM YOUR SERVER.**
