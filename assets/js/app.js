(function () {
    const canvas = document.getElementById('salesChart');
    if (canvas) {
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const values = JSON.parse(canvas.dataset.values || '[]');
        const ctx = canvas.getContext('2d');
        const width = canvas.width;
        const height = canvas.height;

        ctx.clearRect(0, 0, width, height);
        ctx.strokeStyle = '#1d6f5f';
        ctx.lineWidth = 2;

        const max = Math.max(...values, 1);
        values.forEach((v, i) => {
            const x = 30 + (i * ((width - 60) / Math.max(values.length - 1, 1)));
            const y = height - 30 - (v / max) * (height - 60);

            if (i === 0) {
                ctx.beginPath();
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }

            ctx.fillStyle = '#0f172a';
            ctx.fillText(labels[i] || '', x - 8, height - 10);
            ctx.beginPath();
            ctx.arc(x, y, 3, 0, Math.PI * 2);
            ctx.fillStyle = '#1d6f5f';
            ctx.fill();
            ctx.beginPath();
            ctx.moveTo(x, y);
        });

        if (values.length > 1) {
            ctx.beginPath();
            values.forEach((v, i) => {
                const x = 30 + (i * ((width - 60) / Math.max(values.length - 1, 1)));
                const y = height - 30 - (v / max) * (height - 60);
                if (i === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.stroke();
        }
    }

    const lineTable = document.getElementById('lineItems');
    if (!lineTable) {
        return;
    }

    const body = lineTable.querySelector('tbody');
    const addBtn = document.getElementById('addRowBtn');
    const partySelect = document.getElementById('partySelect');
    const partyStateInput = document.getElementById('partyState');
    const businessState = (document.getElementById('businessState')?.value || '').toLowerCase();
    const products = window.PRODUCTS || [];
    const existingItems = Array.isArray(window.EXISTING_ITEMS) ? window.EXISTING_ITEMS : [];
    const existingPartyId = parseInt(window.EXISTING_PARTY_ID || '0', 10) || 0;
    const existingPartyState = String(window.EXISTING_PARTY_STATE || '');
    const mode = partySelect?.dataset.partyKind === 'customer' ? 'sale' : 'purchase';

    function buildProductOptions() {
        return ['<option value="">Select product</option>']
            .concat(products.map((p) => `<option value="${p.id}">${p.product_name} (${p.size})</option>`))
            .join('');
    }

    function addRow(initial) {
        const item = initial || {};
        const selectedProductId = String(item.product_id || '');
        const selectedGst = String(item.gst_percent || 18);
        const qty = parseFloat(item.quantity || 1);
        const rate = parseFloat(item.rate || 0);
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <select name="product_id[]" class="product-id" required>${buildProductOptions()}</select>
            </td>
            <td><input type="number" name="quantity[]" class="qty" min="1" value="${Math.max(1, qty)}" required></td>
            <td><input type="number" name="rate[]" class="rate" step="0.01" min="0" value="${rate}" required></td>
            <td>
                <select name="gst_percent[]" class="gst" required>
                    <option value="5" ${selectedGst === '5' ? 'selected' : ''}>5</option>
                    <option value="12" ${selectedGst === '12' ? 'selected' : ''}>12</option>
                    <option value="18" ${selectedGst === '18' ? 'selected' : ''}>18</option>
                </select>
            </td>
            <td class="taxable">0.00</td>
            <td class="tax">0.00</td>
            <td class="line-total">0.00</td>
            <td><button type="button" class="danger-btn remove">X</button></td>
        `;
        const select = tr.querySelector('.product-id');
        if (selectedProductId !== '') {
            select.value = selectedProductId;
        }
        body.appendChild(tr);
    }

    function updateTotals() {
        let subtotal = 0;
        let cgst = 0;
        let sgst = 0;
        let igst = 0;
        const sameState = (partyStateInput.value || '').toLowerCase() === businessState;

        body.querySelectorAll('tr').forEach((tr) => {
            const qty = parseFloat(tr.querySelector('.qty').value || '0');
            const rate = parseFloat(tr.querySelector('.rate').value || '0');
            const gstPercent = parseFloat(tr.querySelector('.gst').value || '0');

            const taxable = qty * rate;
            const tax = taxable * gstPercent / 100;
            const total = taxable + tax;

            tr.querySelector('.taxable').textContent = taxable.toFixed(2);
            tr.querySelector('.tax').textContent = tax.toFixed(2);
            tr.querySelector('.line-total').textContent = total.toFixed(2);

            subtotal += taxable;
            if (sameState) {
                cgst += tax / 2;
                sgst += tax / 2;
            } else {
                igst += tax;
            }
        });

        document.getElementById('subtotal').textContent = subtotal.toFixed(2);
        document.getElementById('cgst').textContent = cgst.toFixed(2);
        document.getElementById('sgst').textContent = sgst.toFixed(2);
        document.getElementById('igst').textContent = igst.toFixed(2);
        document.getElementById('grandTotal').textContent = (subtotal + cgst + sgst + igst).toFixed(2);
    }

    addBtn.addEventListener('click', () => addRow());

    body.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove')) {
            e.target.closest('tr').remove();
            updateTotals();
        }
    });

    body.addEventListener('change', (e) => {
        const tr = e.target.closest('tr');
        if (!tr) return;

        if (e.target.classList.contains('product-id')) {
            const product = products.find((p) => String(p.id) === e.target.value);
            if (product) {
                tr.querySelector('.rate').value = mode === 'sale' ? product.selling_price : product.purchase_price;
                tr.querySelector('.gst').value = String(product.gst_percent);
            }
        }

        updateTotals();
    });

    body.addEventListener('input', updateTotals);

    partySelect.addEventListener('change', () => {
        const option = partySelect.options[partySelect.selectedIndex];
        partyStateInput.value = option?.dataset.state || '';
        updateTotals();
    });

    if (existingPartyId > 0 && partySelect) {
        partySelect.value = String(existingPartyId);
    }

    if (existingPartyState !== '' && partyStateInput) {
        partyStateInput.value = existingPartyState;
    } else if (partySelect && partyStateInput) {
        const option = partySelect.options[partySelect.selectedIndex];
        partyStateInput.value = option?.dataset.state || '';
    }

    if (existingItems.length > 0) {
        body.innerHTML = '';
        existingItems.forEach((item) => addRow(item));
    } else {
        addRow();
    }

    updateTotals();
})();
