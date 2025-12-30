document.addEventListener('DOMContentLoaded', () => {
    loadInventoryData();
});

async function loadInventoryData() {
    const tableBody = document.querySelector('#inventoryTable tbody');
    
    try {
        const response = await fetch('/api/inventory');
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        
        const data = await response.json();
        
        // Clear old data
        tableBody.innerHTML = '';

        data.forEach(item => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${item.partNo}</td>
                <td>${item.description}</td>
                <td>${item.partFamily}</td>
                <td>${item.materialType}</td>
            `;
            tableBody.appendChild(row);
        });

    } catch (error) {
        console.error('Error fetching inventory:', error);
        tableBody.innerHTML = '<tr><td colspan="4" style="color:red; text-align:center;">ไม่สามารถโหลดข้อมูลได้ (Backend not running?)</td></tr>';
    }
}
