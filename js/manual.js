function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tab-btn");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

document.addEventListener('DOMContentLoaded', () => {
    // Shared Elements

    // Receipt Tab Elements
    const prSearchInput = document.getElementById('prSearchInput');
    const searchPrBtn = document.getElementById('searchPrBtn');
    const prResultsSection = document.getElementById('prResultsSection');
    const prItemsTableBody = document.querySelector('#prItemsTable tbody');
    const displayPrNo = document.getElementById('displayPrNo');

    // Modal Elements
    const receiptModal = document.getElementById('receiptModal');
    const receiptConfirmForm = document.getElementById('receiptConfirmForm');

    // Issue Tab Elements
    const issueForm = document.getElementById('issueDataForm');

    // Date Init
    const todayInputs = document.querySelectorAll('.todayDateInput');
    todayInputs.forEach(input => {
        input.valueAsDate = new Date();
    });

    // Function to handle tab switching based on URL hash
    function handleHashTab() {
        const hash = window.location.hash;
        if (hash === '#issueTab') {
            openTab({ currentTarget: document.querySelector('.tab-btn:nth-child(2)') }, 'issueTab');
        } else if (hash === '#receiptTab' || !hash) {
            openTab({ currentTarget: document.querySelector('.tab-btn:nth-child(1)') }, 'receiptTab');
        }
    }

    // Check URL Hash on load
    handleHashTab();

    // Listen for hash changes (e.g., from Navbar dropdown)
    window.addEventListener('hashchange', handleHashTab);

    const bagTypeMap = {
        '11': 'ถุงปูนซีเมนต์ในประเทศ (กระดาษ)',
        '21': 'ถุงปูนซีเมนต์ในประเทศ (PP)',
        '23': 'ถุงปูนมอร์ตาร์ (PP)',
        '13': 'ถุงปูนมอร์ตาร์ (กระดาษ)',
        '43': 'ถุงปูนมอร์ตาร์ (ฟิล์ม)',
        '52': 'ถุงปูนซีเมนต์ส่งออก (เย็บเชือก)',
        '32': 'ถุงปูนซีเมนต์ส่งออก (PP+กระดาษ)',
        '22': 'ถุงปูนซีเมนต์ส่งออก (PP)',
        '33': 'ถุงปูนมอร์ตาร์ส่งออก (PP+กระดาษ)'
    };

    // --- Tab 1: Receipt Logic ---
    searchPrBtn.addEventListener('click', async () => {
        const searchTerm = prSearchInput.value.trim();
        if (!searchTerm) {
            alert('กรุณากรอกเลขที่ PR หรือ PO');
            return;
        }

        searchPrBtn.disabled = true;
        searchPrBtn.textContent = 'กำลังค้นหา...';
        prItemsTableBody.innerHTML = '<tr><td colspan="9" style="text-align:center;">กำลังดึงข้อมูลจาก IFS...</td></tr>';

        try {
            const response = await fetch(`api/get_pr_items.php?search_term=${encodeURIComponent(searchTerm)}`);
            const result = await response.json();

            if (result.status === 'success') {
                if (result.data.length === 0) {
                    prItemsTableBody.innerHTML = '<tr><td colspan="9" style="text-align:center;">ไม่พบข้อมูลรายการ</td></tr>';
                } else {
                    displayPrNo.textContent = searchTerm;
                    prResultsSection.style.display = 'block';
                    console.log("PR/PO Items Data:", result.data);
                    prItemsTableBody.innerHTML = result.data.map(item => {
                        let statusHtml = '';
                        // Status logic based on user requirements
                        if (!item.ORDER_NO) {
                            statusHtml = '<span class="badge danger">รอ PO</span>';
                        } else if (item.PO_STATE === 'Stopped') {
                            statusHtml = '<span class="badge danger">PO.Stopped</span>';
                        } else {
                            statusHtml = '<span class="badge info" style="background: #e0f2fe; color: #0369a1;">ทำรายการใน IFS ได้แล้ว</span>';
                        }

                        return `
                            <tr>
                                <td style="font-weight:600;">${item.PART_NO}</td>
                                <td style="font-size:12px;">${item.PART_DESCRIPTION || '-'}</td>
                                <td style="font-family:monospace;">${item.REQUISITION_NO}</td>
                                <td style="font-family:monospace;">${item.ORDER_NO || '-'}</td>
                                <td style="font-family:monospace;">${item.LINE_NO}/${item.RELEASE_NO}</td>
                                <td style="text-align:right; font-weight:700;">${parseFloat(item.ORIGINAL_QTY).toLocaleString()}</td>
                                <td>${item.UNIT_MEAS}</td>
                                <td>${statusHtml}</td>
                                <td>
                                    <button class="submit-btn" style="padding:5px 15px; font-size:12px;" onclick="openReceiptModal('${item.REQUISITION_NO}', '${item.ORDER_NO || ''}', '${item.PO_STATE || ''}', '${item.LINE_NO}', '${item.RELEASE_NO}', '${item.PART_NO}', '${item.PART_DESCRIPTION.replace(/'/g, "\\'")}', ${item.ORIGINAL_QTY})">📝 ทำรายการ</button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                }
            } else {
                alert('เกิดข้อผิดพลาด: ' + result.message);
                prItemsTableBody.innerHTML = '';
            }
        } catch (error) {
            alert('ไม่สามารถเชื่อมต่อ API ได้');
        } finally {
            searchPrBtn.disabled = false;
            searchPrBtn.textContent = 'ค้นหา';
        }
    });

    window.openReceiptModal = (reqNo, orderNo, poState, line, rel, partNo, desc, qty) => {
        document.getElementById('modalReqNo').value = reqNo;
        document.getElementById('modalOrderNo').value = orderNo;
        document.getElementById('modalPoState').value = poState;
        document.getElementById('modalLineNo').value = line;
        document.getElementById('modalRelNo').value = rel;
        document.getElementById('modalPartNo').value = partNo;
        document.getElementById('modalPartDesc').value = desc;
        document.getElementById('modalQty').value = qty;

        document.getElementById('displayModalPartNo').textContent = `${partNo} - ${desc}`;
        document.getElementById('displayPrQty').textContent = parseFloat(qty).toLocaleString();

        receiptModal.style.display = 'block';
    };

    window.closeReceiptModal = () => {
        receiptModal.style.display = 'none';
    };

    receiptConfirmForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(receiptConfirmForm);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('api/save_manual_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.status === 'success') {
                alert('บันทึกใบรับเรียบร้อยแล้ว');
                closeReceiptModal();
                fetchManualData();
            } else {
                alert('เกิดข้อผิดพลาด: ' + result.message);
            }
        } catch (error) {
            alert('ไม่สามารถบันทึกข้อมูลได้');
        }
    });

    // --- Tab 2: Issue Logic ---
    issueForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(issueForm);
        const data = Object.fromEntries(formData.entries());

        if (!data.part_no || data.quantity <= 0) {
            alert('กรุณากรอกข้อมูลให้ครบถ้วน');
            return;
        }

        try {
            const response = await fetch('api/save_manual_data.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();

            if (result.status === 'success') {
                alert('บันทึกรายการค้างจ่ายเรียบร้อยแล้ว');
                issueForm.reset();
                todayInputs.forEach(input => input.valueAsDate = new Date());
                fetchManualData();
            } else {
                alert('เกิดข้อผิดพลาด: ' + result.message);
            }
        } catch (error) {
            alert('ไม่สามารถบันทึกข้อมูลได้');
        }
    });

    // --- Shared: History Logic ---
    window.fetchManualData = async function () {
        const receiptBody = document.querySelector('#receiptHistoryTable tbody');
        const issueBody = document.querySelector('#issueHistoryTable tbody');

        if (receiptBody) receiptBody.innerHTML = '<tr><td colspan="9" style="text-align:center;">กำลังโหลด...</td></tr>';
        if (issueBody) issueBody.innerHTML = '<tr><td colspan="9" style="text-align:center;">กำลังโหลด...</td></tr>';

        try {
            const response = await fetch('api/get_manual_data.php');
            const result = await response.json();

            if (result.status === 'success') {
                const receipts = result.data.filter(row => row.data_type === 'RECEIPT');
                const issues = result.data.filter(row => row.data_type === 'ISSUE');

                const renderRows = (data, isReceipt) => {
                    if (data.length === 0) return '<tr><td colspan="9" style="text-align:center;">ไม่พบข้อมูล</td></tr>';
                    return data.map(row => {
                        let statusHtml = '';
                        if (isReceipt) {
                            if (!row.order_no) {
                                statusHtml = '<span class="badge danger">รอ PO</span>';
                            } else if (row.po_state === 'Stopped') {
                                statusHtml = '<span class="badge danger">PO.Stopped</span>';
                            } else {
                                statusHtml = '<span class="badge info" style="background: #e0f2fe; color: #0369a1; white-space: nowrap !important;">ทำรายการใน IFS ได้แล้ว</span>';
                            }
                        } else {
                            statusHtml = '<span class="badge danger">📤 ค้างส่ง (Pending)</span>';
                        }

                        return `
                            <tr>
                                <td style="font-size:12px; color:var(--text-secondary);">${row.created_at}</td>
                                <td style="font-family:monospace; font-weight:600; color:var(--accent-color);">${row.part_no}</td>
                                <td style="font-size:12px;">${row.part_desc || '-'}</td>
                                <td style="font-family:monospace; color:#4b5563;">${row.requisition_no || '-'}</td>
                                <td style="font-family:monospace; color:#4b5563;">${row.order_no || '-'}</td>
                                <td style="text-align:right; font-weight:700; color:${isReceipt ? '#059669' : '#dc2626'}; font-size:1.1em;">
                                    ${parseFloat(row.quantity).toLocaleString()}
                                </td>
                                <td>${row.delivery_date}</td>
                                <td style="font-size:13px; color:var(--text-secondary);">${row.note || '-'}</td>
                                <td>${statusHtml}</td>
                            </tr>
                        `;
                    }).join('');
                };

                if (receiptBody) receiptBody.innerHTML = renderRows(receipts, true);
                if (issueBody) issueBody.innerHTML = renderRows(issues, false);
            }
        } catch (error) {
            console.error("Error loading history:", error);
        }
    }

    fetchManualData();
});
