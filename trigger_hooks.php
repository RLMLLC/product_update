<?php
/**
 * Script to force-trigger the 'actionProductUpdate' hook in PrestaShop 1.7
 *
 * HOW TO USE:
 * 1. Upload this file to your 'admin' folder (e.g., /admin123xyz/trigger_hooks.php)
 * 2. Run it from your browser:
 * https://balsat.com/admin123xyz/trigger_hooks.php?batch=100&page=1
 *
 * - 'batch': Number of products to process at a time (e.g., 100).
 * - 'page': The page or batch number you want to process (e.g., 1, 2, 3...).
 *
 * NOTE: Backup your database BEFORE running this!
 */

// 1. Load PrestaShop Environment
// We move to the PrestaShop root directory to load the config
chdir(dirname(__FILE__).'/..');
require_once(dirname(__FILE__).'/../config/config.inc.php');

// 2. Pagination parameters (to process in batches)
$batchSize = (int)Tools::getValue('batch', 100);
$page = (int)Tools::getValue('page', 1);

if ($batchSize <= 0) {
    $batchSize = 100;
}
if ($page <= 0) {
    $page = 1;
}

$offset = ($page - 1) * $batchSize;

// 3. Increase execution limits (may be necessary)
@ini_set('max_execution_time', 300); // 5 minutes
@ini_set('memory_limit', '512M');

// 4. Get the product IDs
// Modify this query to narrow down only the products you need.
// This query selects ALL active products that have an EAN.
$db = Db::getInstance();
$sql = new DbQuery();
$sql->select('p.`id_product`');
$sql->from('product', 'p');
$sql->where('p.`active` = 1 AND p.`ean13` IS NOT NULL AND p.`ean13` != ""');
$sql->limit($batchSize, $offset);
$sql->orderBy('p.`id_product` ASC');

$productIds = $db->executeS($sql);

if (empty($productIds)) {
    echo "<h1>Process Complete</h1>";
    echo "<p>No more products found to process with these parameters.</p>";
    die();
}

echo "<h1>Processing Page $page ($batchSize products)</h1>";
echo "<ul>";

$context = Context::getContext();
$processed = 0;

// 5. The Magic Loop: Load and Save
foreach ($productIds as $row) {
    $id_product = (int)$row['id_product'];
    
    // Ignore errors if a product fails to load
    try {
        $product = new Product($id_product, false, $context->language->id, $context->shop->id);

        if (Validate::isLoadedObject($product)) {
            // This is the line that triggers the hook!
            $product->save();
            
            echo "<li>OK: Product ID $id_product updated. Hook triggered.</li>";
            $processed++;
        } else {
            echo "<li style='color: red;'>ERROR: Could not load Product ID $id_product.</li>";
        }
        
        // Clear memory
        unset($product);

    } catch (Exception $e) {
        echo "<li style='color: red;'>EXCEPTION processing ID $id_product: " . $e->getMessage() . "</li>";
    }
}

echo "</ul>";
echo "<h3>Total processed in this batch: $processed</h3>";

// 6. Link to the next batch
$nextPage = $page + 1;
$nextUrl = Tools::getHttpHost(true) . $_SERVER['PHP_SELF'] . "?batch=$batchSize&page=$nextPage";

echo "<hr>";
echo "<h2><a href='$nextUrl'>Process Next Batch (Page $nextPage)</a></h2>";

?>
