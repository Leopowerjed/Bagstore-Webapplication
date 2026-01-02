document.addEventListener('DOMContentLoaded', () => {
    const inventoryFilter = document.getElementById('inventoryCategoryFilter');
    const inventoryTableBody = document.querySelector('#inventoryTable tbody');

    // Stats Elements
    const totalOnhandElem = document.getElementById('totalOnhand');
    const totalPRElem = document.getElementById('totalPR');
    const totalPOElem = document.getElementById('totalPO');
    const grandTotalElem = document.getElementById('grandTotal');
    const summaryTitleElem = document.getElementById('summaryTitle');

    const locationModal = document.getElementById('locationModal');
    const prModal = document.getElementById('prModal');
    const poModal = document.getElementById('poModal');

    const locationTableBody = document.querySelector('#locationTable tbody');
    const prTableBody = document.querySelector('#prTable tbody');
    const poTableBody = document.querySelector('#poTable tbody');

    const modalPartNo = document.getElementById('modalPartNo');
    const modalPartDesc = document.getElementById('modalPartDesc');

    // Function to fetch and update inventory
    async function fetchInventory(category) {
        inventoryTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">กำลังโหลดข้อมูล...</td></tr>';

        try {
            const response = await fetch(`api/get_inventory.php?category=${category}`);
            const result = await response.json();

            if (result.status === 'success') {
                updateTable(result.data);
                updateSummary(result.summary);
            } else {
                throw new Error(result.message);
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            const alertBox = document.getElementById('alertBox');
            const alertContent = document.getElementById('alertContent');
            if (alertBox && alertContent) {
                alertBox.style.display = 'block';
                alertContent.innerHTML = `<p style="color:red;">❌ ไม่สามารถดึงข้อมูลจาก IFS ได้: ${error.message}</p>
                    <p style="font-size:0.8em; margin-top:5px;">คำแนะนำ: ตรวจสอบว่าเปิด Apache และติดตั้ง Oracle Client เรียบร้อยแล้ว</p>`;
            }
            inventoryTableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:red;">เกิดข้อผิดพลาดในการโหลดข้อมูล</td></tr>`;
        }
    }

    function updateTable(data) {
        if (data.length === 0) {
            inventoryTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">ไม่พบข้อมูลในกลุ่มนี้</td></tr>';
            return;
        }

        inventoryTableBody.innerHTML = data.map(item => `
            <tr>
                <td class="part-no">${item.PART_NO}</td>
                <td>${item.DESCRIPTION || '-'}</td>
                <td style="font-weight:bold;">
                    <span class="on-hand-link" 
                          data-part-no="${item.PART_NO}" 
                          data-desc="${item.DESCRIPTION || ''}">
                        ${Math.round(item.TOTAL_ONHAND || 0).toLocaleString()}
                    </span>
                </td>
                <td>
                    <span class="pr-link" 
                          data-part-no="${item.PART_NO}" 
                          data-desc="${item.DESCRIPTION || ''}">
                        ${Math.round(item.TOTAL_PR || 0).toLocaleString()}
                    </span>
                </td>
                <td>
                    <span class="po-link" 
                          data-part-no="${item.PART_NO}" 
                          data-desc="${item.DESCRIPTION || ''}">
                        ${Math.round(item.TOTAL_PO || 0).toLocaleString()}
                    </span>
                </td>
                <td><span class="badge ${(parseFloat(item.TOTAL_ONHAND) + parseFloat(item.TOTAL_PR) + parseFloat(item.TOTAL_PO)) > 0 ? 'success' : 'warning'}">
                    ${(parseFloat(item.TOTAL_ONHAND) + parseFloat(item.TOTAL_PR) + parseFloat(item.TOTAL_PO)) > 0 ? 'Active' : 'Empty'}
                </span></td>
            </tr>
        `).join('');

        // Add event listeners
        document.querySelectorAll('.on-hand-link').forEach(link => {
            link.addEventListener('click', (e) => {
                const partNo = e.target.getAttribute('data-part-no');
                const desc = e.target.getAttribute('data-desc');
                showLocationDetail(partNo, desc);
            });
        });

        document.querySelectorAll('.pr-link').forEach(link => {
            link.addEventListener('click', (e) => {
                const partNo = e.target.getAttribute('data-part-no');
                const desc = e.target.getAttribute('data-desc');
                showPRDetail(partNo, desc);
            });
        });

        document.querySelectorAll('.po-link').forEach(link => {
            link.addEventListener('click', (e) => {
                const partNo = e.target.getAttribute('data-part-no');
                const desc = e.target.getAttribute('data-desc');
                showPODetail(partNo, desc);
            });
        });
    }

    async function showLocationDetail(partNo, desc) {
        modalPartNo.textContent = partNo;
        modalPartDesc.textContent = desc;
        locationTableBody.innerHTML = '<tr><td colspan="3" style="text-align:center;">กำลังโหลดข้อมูล...</td></tr>';
        locationModal.style.display = 'block';

        try {
            const response = await fetch(`api/get_inventory_locations.php?part_no=${encodeURIComponent(partNo)}`);
            const result = await response.json();

            if (result.status === 'success') {
                if (result.data.length === 0) {
                    locationTableBody.innerHTML = '<tr><td colspan="3" style="text-align:center;">ไม่พบข้อมูล Location</td></tr>';
                } else {
                    const total = result.data.reduce((sum, loc) => sum + parseFloat(loc.TOTAL_ONHAND || 0), 0);

                    // Grouping Logic
                    const groups = {
                        'ค้าง Move': ['3000'],
                        'Store': ['3001', '3002', '3091', '3092'],
                        'Warehouse': ['3003', '3004', '3100'],
                        'Product': ['3005', '3006', '3007', '3008', '3009', '3010', '3030', '3101']
                    };

                    const groupTotals = {
                        'ค้าง Move': 0,
                        'Store': 0,
                        'Warehouse': 0,
                        'Product': 0,
                        'Other': 0
                    };

                    result.data.forEach(loc => {
                        let found = false;
                        for (const [groupName, locations] of Object.entries(groups)) {
                            if (locations.includes(loc.LOCATION_NO)) {
                                groupTotals[groupName] += parseFloat(loc.TOTAL_ONHAND || 0);
                                found = true;
                                break;
                            }
                        }
                        if (!found) groupTotals['Other'] += parseFloat(loc.TOTAL_ONHAND || 0);
                    });

                    // Update Group Summary UI
                    const summaryElem = document.getElementById('locationGroupSummary');
                    if (summaryElem) {
                        summaryElem.innerHTML = `
                            <div class="summary-grid">
                                ${Object.entries(groupTotals).filter(([name, val]) => val > 0 || name !== 'Other').map(([name, val]) => `
                                    <div class="summary-item">
                                        <div class="summary-label">${name}</div>
                                        <div class="summary-value">${Math.round(val).toLocaleString()}</div>
                                    </div>
                                `).join('')}
                            </div>
                        `;
                    }

                    locationTableBody.innerHTML = result.data.map(loc => `
                        <tr>
                            <td style="font-family:monospace; font-weight:bold;">${loc.LOCATION_NO}</td>
                            <td style="color:var(--text-secondary);">${loc.LOCATION_NAME || '-'}</td>
                            <td style="text-align:right; font-weight:700;">${Math.round(parseFloat(loc.TOTAL_ONHAND)).toLocaleString()}</td>
                        </tr>
                    `).join('');

                    // Add Total Row
                    locationTableBody.innerHTML += `
                        <tr style="background-color: rgba(0,0,0,0.05); font-weight: bold; border-top: 2px solid var(--border-color);">
                            <td colspan="2" style="text-align:right;">ผลรวม:</td>
                            <td style="text-align:right; color:var(--primary-color); font-size: 1.1em;">${Math.round(total).toLocaleString()}</td>
                        </tr>
                    `;
                }
            } else {
                locationTableBody.innerHTML = `<tr><td colspan="3" style="text-align:center; color:red;">Error: ${result.message}</td></tr>`;
            }
        } catch (error) {
            locationTableBody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:red;">ไม่สามารถเชื่อมต่อ API ได้</td></tr>';
        }
    }

    async function showPRDetail(partNo, desc) {
        const modalPartNoPr = document.querySelector('.pr-part-no');
        const modalPartDescPr = document.querySelector('.pr-part-desc');
        modalPartNoPr.textContent = partNo;
        modalPartDescPr.textContent = desc;
        prTableBody.innerHTML = '<tr><td colspan="4" style="text-align:center;">กำลังโหลดข้อมูล...</td></tr>';
        prModal.style.display = 'block';

        try {
            const response = await fetch(`api/get_inventory_pr_details.php?part_no=${encodeURIComponent(partNo)}`);
            const result = await response.json();

            if (result.status === 'success') {
                if (result.data.length === 0) {
                    prTableBody.innerHTML = '<tr><td colspan="4" style="text-align:center;">ไม่พบรายการ PR</td></tr>';
                } else {
                    const total = result.data.reduce((sum, row) => sum + parseFloat(row.ORIGINAL_QTY || 0), 0);
                    prTableBody.innerHTML = result.data.map(row => `
                        <tr>
                            <td style="font-family:monospace;">${row.REQUISITION_NO}</td>
                            <td style="font-size:13px; color:var(--text-secondary);">${formatDate(row.REQUISITION_DATE)}</td>
                            <td><span class="badge warning">${row.STATE}</span></td>
                            <td style="text-align:right; font-weight:700;">${Math.round(parseFloat(row.ORIGINAL_QTY)).toLocaleString()}</td>
                        </tr>
                    `).join('');

                    // Add Total Row
                    prTableBody.innerHTML += `
                        <tr style="background-color: rgba(0,0,0,0.05); font-weight: bold; border-top: 2px solid var(--border-color);">
                            <td colspan="3" style="text-align:right;">ผลรวม:</td>
                            <td style="text-align:right; color:#2563eb; font-size: 1.1em;">${Math.round(total).toLocaleString()}</td>
                        </tr>
                    `;
                }
            } else {
                prTableBody.innerHTML = `<tr><td colspan="4" style="text-align:center; color:red;">Error: ${result.message}</td></tr>`;
            }
        } catch (error) {
            prTableBody.innerHTML = '<tr><td colspan="4" style="text-align:center; color:red;">ไม่สามารถเชื่อมต่อ API ได้</td></tr>';
        }
    }

    async function showPODetail(partNo, desc) {
        const modalPartNoPo = document.querySelector('.po-part-no');
        const modalPartDescPo = document.querySelector('.po-part-desc');
        modalPartNoPo.textContent = partNo;
        modalPartDescPo.textContent = desc;
        poTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">กำลังโหลดข้อมูล...</td></tr>';
        poModal.style.display = 'block';

        try {
            const response = await fetch(`api/get_inventory_po_details.php?part_no=${encodeURIComponent(partNo)}`);
            const result = await response.json();

            if (result.status === 'success') {
                if (result.data.length === 0) {
                    poTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">ไม่พบรายการ PO</td></tr>';
                } else {
                    const totalDue = result.data.reduce((sum, row) => sum + parseFloat(row.BUY_QTY_DUE || 0), 0);
                    const totalBalance = result.data.reduce((sum, row) => sum + parseFloat(row.BALANCE || 0), 0);
                    poTableBody.innerHTML = result.data.map(row => `
                        <tr>
                            <td style="font-family:monospace;">${row.ORDER_NO}</td>
                            <td style="font-size:13px; color:var(--text-secondary);">${formatDate(row.ORDER_DATE)}</td>
                            <td><span class="badge success">${row.STATE}</span></td>
                            <td style="text-align:right;">${Math.round(parseFloat(row.BUY_QTY_DUE)).toLocaleString()}</td>
                            <td style="text-align:right; font-weight:700; color:var(--accent-color);">${Math.round(parseFloat(row.BALANCE)).toLocaleString()}</td>
                        </tr>
                    `).join('');

                    // Add Total Row
                    poTableBody.innerHTML += `
                        <tr style="background-color: rgba(0,0,0,0.05); font-weight: bold; border-top: 2px solid var(--border-color);">
                            <td colspan="3" style="text-align:right;">ผลรวม:</td>
                            <td style="text-align:right;">${Math.round(totalDue).toLocaleString()}</td>
                            <td style="text-align:right; color:var(--accent-color); font-size: 1.1em;">${Math.round(totalBalance).toLocaleString()}</td>
                        </tr>
                    `;
                }
            } else {
                poTableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">Error: ${result.message}</td></tr>`;
            }
        } catch (error) {
            poTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:red;">ไม่สามารถเชื่อมต่อ API ได้</td></tr>';
        }
    }

    function formatDate(dateStr) {
        if (!dateStr) return '-';
        const d = new Date(dateStr);
        return d.toLocaleDateString('th-TH', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    function updateSummary(summary) {
        if (!summary) return;

        totalOnhandElem.textContent = Math.round(summary.totalOnhand).toLocaleString();
        totalPRElem.textContent = Math.round(summary.totalPR).toLocaleString();
        totalPOElem.textContent = Math.round(summary.totalPO).toLocaleString();
        grandTotalElem.textContent = Math.round(summary.grandTotal).toLocaleString();
    }

    // Modal Close logic
    document.querySelectorAll('.close-btn').forEach(btn => {
        btn.onclick = () => {
            const modalId = btn.getAttribute('data-modal') || 'locationModal';
            document.getElementById(modalId).style.display = 'none';
        };
    });

    window.onclick = (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    }


    // filter change
    inventoryFilter.addEventListener('change', (e) => {
        const selectedText = e.target.options[e.target.selectedIndex].text
            .replace('⭐ ', '')
            .replace(/^\d+\.\)\s*/, ''); // Remove "1.) ", "2.) ", etc.
        summaryTitleElem.textContent = `📦 สรุปปริมาณถุง: ${selectedText}`;
        fetchInventory(e.target.value);
    });

    // Add Real-time search filter
    const searchInput = document.getElementById('inventorySearch');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            const rows = inventoryTableBody.querySelectorAll('tr');

            rows.forEach(row => {
                const partNo = row.cells[0]?.textContent.toLowerCase() || '';
                const partDesc = row.cells[1]?.textContent.toLowerCase() || '';
                if (partNo.includes(term) || partDesc.includes(term)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Initial load
    const initialText = inventoryFilter.options[inventoryFilter.selectedIndex].text
        .replace('⭐ ', '')
        .replace(/^\d+\.\)\s*/, '');
    summaryTitleElem.textContent = `📦 สรุปปริมาณถุง: ${initialText}`;
    fetchInventory('11');
});

