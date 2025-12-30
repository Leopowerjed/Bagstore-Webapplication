document.addEventListener('DOMContentLoaded', () => {
    const inventoryFilter = document.getElementById('inventoryCategoryFilter');
    const summaryFilter = document.getElementById('bagTypeFilter');
    const inventoryTableBody = document.querySelector('#inventoryTable tbody');

    // Stats Elements
    const totalOnhandElem = document.getElementById('totalOnhand');
    const totalPRElem = document.getElementById('totalPR');
    const totalPOElem = document.getElementById('totalPO');
    const grandTotalElem = document.getElementById('grandTotal');

    const locationModal = document.getElementById('locationModal');
    const locationTableBody = document.querySelector('#locationTable tbody');
    const modalPartNo = document.getElementById('modalPartNo');
    const modalPartDesc = document.getElementById('modalPartDesc');
    const closeBtn = document.querySelector('.close-btn');

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
                <td style="color:#2563eb;">${Math.round(item.TOTAL_PR || 0).toLocaleString()}</td>
                <td style="color:#7c3aed;">${Math.round(item.TOTAL_PO || 0).toLocaleString()}</td>
                <td><span class="badge ${(parseFloat(item.TOTAL_ONHAND) + parseFloat(item.TOTAL_PR) + parseFloat(item.TOTAL_PO)) > 0 ? 'success' : 'warning'}">
                    ${(parseFloat(item.TOTAL_ONHAND) + parseFloat(item.TOTAL_PR) + parseFloat(item.TOTAL_PO)) > 0 ? 'Active' : 'Empty'}
                </span></td>
            </tr>
        `).join('');

        // Add event listeners to on-hand links
        document.querySelectorAll('.on-hand-link').forEach(link => {
            link.addEventListener('click', (e) => {
                const partNo = e.target.getAttribute('data-part-no');
                const desc = e.target.getAttribute('data-desc');
                showLocationDetail(partNo, desc);
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
                    locationTableBody.innerHTML = result.data.map(loc => `
                        <tr>
                            <td style="font-family:monospace; font-weight:bold;">${loc.LOCATION_NO}</td>
                            <td style="color:var(--text-secondary);">${loc.LOCATION_NAME || '-'}</td>
                            <td style="text-align:right; font-weight:700;">${Math.round(loc.TOTAL_ONHAND).toLocaleString()}</td>
                        </tr>
                    `).join('');
                }
            } else {
                locationTableBody.innerHTML = `<tr><td colspan="3" style="text-align:center; color:red;">Error: ${result.message}</td></tr>`;
            }
        } catch (error) {
            locationTableBody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:red;">ไม่สามารถเชื่อมต่อ API ได้</td></tr>';
        }
    }

    function updateSummary(summary) {
        if (!summary) return;

        totalOnhandElem.textContent = Math.round(summary.totalOnhand).toLocaleString();
        totalPRElem.textContent = Math.round(summary.totalPR).toLocaleString();
        totalPOElem.textContent = Math.round(summary.totalPO).toLocaleString();
        grandTotalElem.textContent = Math.round(summary.grandTotal).toLocaleString();
    }

    // Modal Close logic
    closeBtn.onclick = () => locationModal.style.display = 'none';
    window.onclick = (e) => {
        if (e.target == locationModal) locationModal.style.display = 'none';
    }

    // sync filters if one changes (optional design choice)
    inventoryFilter.addEventListener('change', (e) => {
        const val = e.target.value;
        summaryFilter.value = val;
        fetchInventory(val);
    });

    summaryFilter.addEventListener('change', (e) => {
        const val = e.target.value;
        inventoryFilter.value = val;
        fetchInventory(val);
    });

    // Initial load
    fetchInventory('11');
});

