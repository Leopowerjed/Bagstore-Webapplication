document.addEventListener('DOMContentLoaded', () => {
    const inventoryFilter = document.getElementById('inventoryCategoryFilter');
    const summaryFilter = document.getElementById('bagTypeFilter');
    const inventoryTableBody = document.querySelector('#inventoryTable tbody');

    // Stats Elements
    const totalOnhandElem = document.getElementById('totalOnhand');
    const totalPRElem = document.getElementById('totalPR');
    const totalPOElem = document.getElementById('totalPO');
    const grandTotalElem = document.getElementById('grandTotal');

    // Function to fetch and update inventory
    async function fetchInventory(category) {
        inventoryTableBody.innerHTML = '<tr><td colspan="5" style="text-align:center;">กำลังโหลดข้อมูล...</td></tr>';

        try {
            const response = await fetch(`api/get_inventory.php?category=${category}`);
            const result = await response.json();

            if (result.status === 'success') {
                updateTable(result.data);
                updateSummary(result.data);
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
                <td>${item.PART_NO}</td>
                <td>${item.DESCRIPTION || '-'}</td>
                <td style="font-weight:bold;">${Math.round(item.TOTAL_ONHAND || 0).toLocaleString()}</td>
                <td style="color:#2563eb;">${Math.round(item.TOTAL_PR || 0).toLocaleString()}</td>
                <td style="color:#7c3aed;">${Math.round(item.TOTAL_PO || 0).toLocaleString()}</td>
                <td><span class="badge ${(parseFloat(item.TOTAL_ONHAND) + parseFloat(item.TOTAL_PR) + parseFloat(item.TOTAL_PO)) > 0 ? 'success' : 'warning'}">
                    ${(parseFloat(item.TOTAL_ONHAND) + parseFloat(item.TOTAL_PR) + parseFloat(item.TOTAL_PO)) > 0 ? 'Active' : 'Empty'}
                </span></td>
            </tr>
        `).join('');
    }

    function updateSummary(summary) {
        if (!summary) return;

        totalOnhandElem.textContent = Math.round(summary.totalOnhand).toLocaleString();
        totalPRElem.textContent = Math.round(summary.totalPR).toLocaleString();
        totalPOElem.textContent = Math.round(summary.totalPO).toLocaleString();
        grandTotalElem.textContent = Math.round(summary.grandTotal).toLocaleString();
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
    fetchInventory('');
});
