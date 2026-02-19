<h2>Smart Operations</h2>
<section class="card">
    <form method="get" class="grid four">
        <input type="hidden" name="route" value="smart/index">
        <label>From<input type="date" name="from" value="<?= e($from) ?>"></label>
        <label>To<input type="date" name="to" value="<?= e($to) ?>"></label>
        <label>Month<input type="month" name="month" value="<?= e($month) ?>"></label>
        <button type="submit">Refresh Smart Metrics</button>
    </form>
    <div class="toolbar">
        <a class="btn" href="index.php?route=smart/complianceCsv&month=<?= e($month) ?>">GST Compliance CSV</a>
    </div>
</section>

<section class="grid two">
    <div class="card">
        <h3>Smart Reorder Engine</h3>
        <table>
            <thead><tr><th>SKU</th><th>Product</th><th>Avg Daily</th><th>Stock</th><th>Suggested PO Qty</th></tr></thead>
            <tbody>
            <?php foreach ($data['reorder'] as $r): ?>
                <tr><td><?= e($r['sku']) ?></td><td><?= e($r['product_name']) ?></td><td><?= e($r['avg_daily']) ?></td><td><?= e($r['stock_quantity']) ?></td><td><strong><?= e($r['suggested_order_qty']) ?></strong></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Demand Forecast (30/60/90)</h3>
        <table>
            <thead><tr><th>SKU</th><th>30d</th><th>60d</th><th>90d</th></tr></thead>
            <tbody>
            <?php foreach ($data['forecast'] as $f): ?>
                <tr><td><?= e($f['sku']) ?></td><td><?= e($f['forecast_30d']) ?></td><td><?= e($f['forecast_60d']) ?></td><td><?= e($f['forecast_90d']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <h3>Margin Intelligence</h3>
    <table>
        <thead><tr><th>Date</th><th>Invoice</th><th>Product</th><th>Revenue</th><th>Est. Cost</th><th>Margin</th></tr></thead>
        <tbody>
        <?php foreach ($data['margin'] as $m): ?>
            <tr>
                <td><?= e($m['date']) ?></td>
                <td><?= e($m['invoice_no']) ?></td>
                <td><?= e($m['product_name']) ?></td>
                <td><?= number_format((float)$m['gross_revenue'], 2) ?></td>
                <td><?= number_format((float)$m['estimated_cost'], 2) ?></td>
                <td><strong><?= number_format((float)$m['margin'], 2) ?></strong></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="grid two">
    <div class="card">
        <h3>Batch + Expiry Tracking (FEFO)</h3>
        <form method="post" class="form-grid" action="index.php?route=smart/index">
            <input type="hidden" name="action" value="add_batch">
            <label>Product
                <select name="product_id" required>
                    <?php foreach ($data['products'] as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['product_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Warehouse
                <select name="warehouse_id" required>
                    <?php foreach ($data['warehouses'] as $w): ?><option value="<?= (int)$w['id'] ?>"><?= e($w['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Batch No<input type="text" name="batch_no" required></label>
            <label>MFG<input type="date" name="mfg_date" required></label>
            <label>Expiry<input type="date" name="expiry_date" required></label>
            <label>Qty<input type="number" name="quantity" min="1" required></label>
            <button type="submit">Add Batch</button>
        </form>
        <table>
            <thead><tr><th>Product</th><th>Batch</th><th>Expiry</th><th>Qty</th></tr></thead>
            <tbody>
            <?php foreach ($data['expiring_batches'] as $b): ?>
                <tr><td><?= e($b['product_name']) ?></td><td><?= e($b['batch_no']) ?></td><td><?= e($b['expiry_date']) ?></td><td><?= e($b['quantity']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Credit Control + Ageing</h3>
        <form method="post" class="form-grid" action="index.php?route=smart/index">
            <input type="hidden" name="action" value="record_payment">
            <label>Invoice
                <select name="sale_id" required>
                    <?php foreach ($data['sales'] as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['invoice_no']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Amount<input type="number" step="0.01" name="amount" required></label>
            <label>Mode
                <select name="payment_mode"><option>UPI</option><option>Bank</option><option>Cash</option></select>
            </label>
            <button type="submit">Record Payment</button>
        </form>
        <table>
            <thead><tr><th>Invoice</th><th>Customer</th><th>Outstanding</th><th>Bucket</th></tr></thead>
            <tbody>
            <?php foreach ($data['aging'] as $a): ?>
                <tr><td><?= e($a['invoice_no']) ?></td><td><?= e($a['customer_name']) ?></td><td><?= number_format((float)$a['outstanding'], 2) ?></td><td><?= e($a['bucket']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="grid two">
    <div class="card">
        <h3>Purchase Optimization (Supplier Rates)</h3>
        <form method="post" class="form-grid" action="index.php?route=smart/index">
            <input type="hidden" name="action" value="save_supplier_rate">
            <label>Product
                <select name="product_id" required>
                    <?php foreach ($data['products'] as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['product_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Supplier
                <select name="supplier_id" required>
                    <?php foreach ($data['suppliers'] as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Rate<input type="number" step="0.01" name="rate" required></label>
            <button type="submit">Save Rate</button>
        </form>
        <table>
            <thead><tr><th>Product</th><th>Supplier</th><th>Rate</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($data['supplier_rates'] as $r): ?>
                <tr><td><?= e($r['product_name']) ?></td><td><?= e($r['supplier_name']) ?></td><td><?= e($r['rate']) ?></td><td><?= e($r['recorded_on']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Multi Price Lists</h3>
        <form method="post" class="form-grid" action="index.php?route=smart/index">
            <input type="hidden" name="action" value="create_price_list">
            <label>List Name<input type="text" name="name" required></label>
            <label>Channel
                <select name="channel"><option>Retail</option><option>Wholesale</option><option>Distributor</option></select>
            </label>
            <button type="submit">Create Price List</button>
        </form>
        <form method="post" class="form-grid" action="index.php?route=smart/index">
            <input type="hidden" name="action" value="add_price_item">
            <label>Price List
                <select name="price_list_id" required>
                    <?php foreach ($data['price_lists'] as $l): ?><option value="<?= (int)$l['id'] ?>"><?= e($l['name']) ?> (<?= e($l['channel']) ?>)</option><?php endforeach; ?>
                </select>
            </label>
            <label>Product
                <select name="product_id" required>
                    <?php foreach ($data['products'] as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['product_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Price<input type="number" step="0.01" name="price" required></label>
            <button type="submit">Save Item Price</button>
        </form>
    </div>
</section>

<section class="grid two">
    <div class="card">
        <h3>Multi-Warehouse + Transfer</h3>
        <form method="post" class="form-grid" action="index.php?route=smart/index">
            <input type="hidden" name="action" value="add_warehouse">
            <label>Name<input type="text" name="name" required></label>
            <label>State<input type="text" name="state" required></label>
            <button type="submit">Add Warehouse</button>
        </form>
        <form method="post" class="form-grid" action="index.php?route=smart/index">
            <input type="hidden" name="action" value="transfer_stock">
            <label>Product
                <select name="product_id" required>
                    <?php foreach ($data['products'] as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['product_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>From
                <select name="from_warehouse_id" required>
                    <?php foreach ($data['warehouses'] as $w): ?><option value="<?= (int)$w['id'] ?>"><?= e($w['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>To
                <select name="to_warehouse_id" required>
                    <?php foreach ($data['warehouses'] as $w): ?><option value="<?= (int)$w['id'] ?>"><?= e($w['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Qty<input type="number" name="quantity" min="1" required></label>
            <button type="submit">Transfer</button>
        </form>
        <table>
            <thead><tr><th>Warehouse</th><th>Product</th><th>Qty</th></tr></thead>
            <tbody>
            <?php foreach ($data['stock_by_wh'] as $s): ?>
                <tr><td><?= e($s['warehouse']) ?></td><td><?= e($s['product_name']) ?></td><td><?= e($s['quantity']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Returns / Credit Notes</h3>
        <form method="post" class="form-grid" action="index.php?route=smart/index">
            <input type="hidden" name="action" value="create_return">
            <label>Sale Invoice
                <select name="sale_id" required>
                    <?php foreach ($data['sales'] as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['invoice_no']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Product
                <select name="product_id" required>
                    <?php foreach ($data['products'] as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['product_name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Qty<input type="number" name="quantity" min="1" required></label>
            <label>Reason<input type="text" name="reason" required></label>
            <button type="submit">Create Credit Note</button>
        </form>

        <h3>Barcode / QR Readiness</h3>
        <form method="get" class="form-grid" action="index.php">
            <input type="hidden" name="route" value="smart/barcode">
            <label>Product
                <select name="product_id" required>
                    <?php foreach ($data['products'] as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['product_name']) ?> - <?= e($p['sku']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Generate Barcode Payload</button>
        </form>
    </div>
</section>

<section class="grid two">
    <div class="card">
        <h3>Approvals Workflow</h3>
        <form method="post" class="form-grid" action="index.php?route=smart/index">
            <input type="hidden" name="action" value="create_approval">
            <label>Type
                <select name="approval_type"><option>DISCOUNT</option><option>MANUAL_STOCK_ADJUSTMENT</option><option>LOW_MARGIN_SALE</option></select>
            </label>
            <label>Reference<input type="text" name="reference_no" required></label>
            <label>Notes<input type="text" name="notes" required></label>
            <button type="submit">Submit Approval</button>
        </form>
        <table>
            <thead><tr><th>Type</th><th>Ref</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($data['approvals'] as $a): ?>
                <tr>
                    <td><?= e($a['approval_type']) ?></td>
                    <td><?= e($a['reference_no']) ?></td>
                    <td><?= e($a['status']) ?></td>
                    <td>
                        <?php if ($a['status'] === 'PENDING'): ?>
                            <form method="post" action="index.php?route=smart/index" style="display:inline;">
                                <input type="hidden" name="action" value="review_approval">
                                <input type="hidden" name="approval_id" value="<?= (int)$a['id'] ?>">
                                <input type="hidden" name="status" value="APPROVED">
                                <button type="submit">Approve</button>
                            </form>
                            <form method="post" action="index.php?route=smart/index" style="display:inline;">
                                <input type="hidden" name="action" value="review_approval">
                                <input type="hidden" name="approval_id" value="<?= (int)$a['id'] ?>">
                                <input type="hidden" name="status" value="REJECTED">
                                <button type="submit" class="secondary">Reject</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <h3>Automation + Notifications</h3>
        <form method="post" action="index.php?route=smart/index">
            <input type="hidden" name="action" value="run_notifications">
            <button type="submit">Queue Daily Summary</button>
        </form>
        <table>
            <thead><tr><th>Channel</th><th>Status</th><th>Message</th><th>At</th></tr></thead>
            <tbody>
            <?php foreach ($data['notifications'] as $n): ?>
                <tr><td><?= e($n['channel']) ?></td><td><?= e($n['status']) ?></td><td><?= e($n['message']) ?></td><td><?= e($n['created_at']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h3>E-Invoice / E-Way JSON</h3>
        <p class="muted">Generate GST-compliant draft payload for any invoice.</p>
        <form method="get" class="form-grid" action="index.php">
            <input type="hidden" name="route" value="smart/einvoice">
            <label>Sale Invoice
                <select name="sale_id" required>
                    <?php foreach ($data['sales'] as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['invoice_no']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Generate JSON</button>
        </form>
    </div>
</section>

<section class="card">
    <h3>Audit Trail</h3>
    <table>
        <thead><tr><th>Time</th><th>User ID</th><th>Action</th><th>Entity</th><th>Meta</th></tr></thead>
        <tbody>
        <?php foreach ($data['audit'] as $a): ?>
            <tr><td><?= e($a['created_at']) ?></td><td><?= e($a['user_id']) ?></td><td><?= e($a['action']) ?></td><td><?= e($a['entity']) ?></td><td><?= e((string)$a['meta']) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
