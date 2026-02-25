<h2>Preview Imported Purchase</h2>

<section class="card">
    <p class="muted">
        Review and edit rows before final save. Rows with no mapped product must be selected manually.
    </p>
    <div class="grid two">
        <div><strong>Supplier:</strong> <?= e($supplier['name'] ?? ('ID #' . (int)($header['supplier_id'] ?? 0))) ?></div>
        <div><strong>Supplier State:</strong> <?= e($header['supplier_state'] ?? '-') ?></div>
        <div><strong>Purchase Invoice:</strong> <?= e($header['purchase_invoice_no'] ?? '-') ?></div>
        <div><strong>Date:</strong> <?= e($header['date'] ?? '-') ?></div>
        <div><strong>Status:</strong> <?= e($header['status'] ?? 'DRAFT') ?></div>
        <div><strong>Source File:</strong> <?= e($header['source_file'] ?? '-') ?></div>
    </div>
</section>

<form method="post" action="index.php?route=purchase/finalizeImport">
        <?= csrf_field() ?>
    <section class="card">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>Parsed Item</th>
                <th>SKU</th>
                <th>Mapped Product</th>
                <th>Qty</th>
                <th>Rate</th>
                <th>GST %</th>
                <th>Taxable</th>
                <th>Tax</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $i => $row): ?>
                <?php
                $qty = max(1, (int)($row['quantity'] ?? 1));
                $rate = (float)($row['rate'] ?? 0);
                $gst = (float)($row['gst_percent'] ?? 18);
                ?>
                <tr>
                    <td><?= (int)$i + 1 ?></td>
                    <td><?= e($row['product_name'] ?? '') ?></td>
                    <td><?= e($row['sku'] ?? '') ?></td>
                    <td>
                        <select name="product_id[<?= (int)$i ?>]" required>
                            <option value="">Select Product</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= (int)$p['id'] === (int)($row['product_id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= e($p['product_name']) ?> (<?= e($p['sku']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td><input type="number" name="quantity[<?= (int)$i ?>]" min="1" step="1" value="<?= (int)$qty ?>" required></td>
                    <td><input type="number" name="rate[<?= (int)$i ?>]" min="0.01" step="0.01" value="<?= number_format($rate, 2, '.', '') ?>" required></td>
                    <td>
                        <select name="gst_percent[<?= (int)$i ?>]">
                            <option value="5" <?= abs($gst - 5.0) < 0.01 ? 'selected' : '' ?>>5%</option>
                            <option value="12" <?= abs($gst - 12.0) < 0.01 ? 'selected' : '' ?>>12%</option>
                            <option value="18" <?= abs($gst - 18.0) < 0.01 ? 'selected' : '' ?>>18%</option>
                        </select>
                    </td>
                    <td class="js-taxable">0.00</td>
                    <td class="js-tax">0.00</td>
                    <td class="js-total">0.00</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
            <tr>
                <th colspan="7" style="text-align:right;">Subtotal</th>
                <th id="js-subtotal">0.00</th>
                <th id="js-taxsum">0.00</th>
                <th id="js-grandsum">0.00</th>
            </tr>
            </tfoot>
        </table>

        <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
            <button type="submit">Finalize and Create Purchase</button>
            <button type="submit" class="secondary" formaction="index.php?route=purchase/clearImport" formnovalidate onclick="return confirm('Discard this import preview?')">Cancel Import</button>
            <a class="btn secondary" href="index.php?route=purchase/import">Re-upload File</a>
        </div>
    </section>
</form>

<script>
(function () {
    const rows = Array.from(document.querySelectorAll('tbody tr'));
    const subtotalEl = document.getElementById('js-subtotal');
    const taxSumEl = document.getElementById('js-taxsum');
    const grandEl = document.getElementById('js-grandsum');

    function num(v) {
        const n = parseFloat(v);
        return Number.isFinite(n) ? n : 0;
    }

    function recalc() {
        let subtotal = 0;
        let taxTotal = 0;
        let grand = 0;

        rows.forEach((tr) => {
            const qty = num(tr.querySelector('input[name^="quantity["]').value);
            const rate = num(tr.querySelector('input[name^="rate["]').value);
            const gst = num(tr.querySelector('select[name^="gst_percent["]').value);

            const taxable = qty * rate;
            const tax = taxable * gst / 100;
            const total = taxable + tax;

            tr.querySelector('.js-taxable').textContent = taxable.toFixed(2);
            tr.querySelector('.js-tax').textContent = tax.toFixed(2);
            tr.querySelector('.js-total').textContent = total.toFixed(2);

            subtotal += taxable;
            taxTotal += tax;
            grand += total;
        });

        subtotalEl.textContent = subtotal.toFixed(2);
        taxSumEl.textContent = taxTotal.toFixed(2);
        grandEl.textContent = grand.toFixed(2);
    }

    document.addEventListener('input', (e) => {
        if (e.target.matches('input[name^="quantity["], input[name^="rate["], select[name^="gst_percent["]')) {
            recalc();
        }
    });

    recalc();
})();
</script>
