// Dynamic Navbar Rendering with Active State
document.addEventListener('DOMContentLoaded', () => {
    const navbarElement = document.getElementById('navbar');
    if (!navbarElement) return;

    // Determine current page
    const currentPage = window.location.pathname.split("/").pop() || 'index.html';

    navbarElement.innerHTML = `
    <nav>
        <h1>BAGSTORE Webapplication</h1>
        <p>โปรแกรมรับ-จ่าย บรรจุภัณฑ์ เชื่อมต่อ IFS 7.5</p>
    </nav>

    <div class="menu-bar">
        <ul>
            <li><a href="index.html" class="${currentPage === 'index.html' || currentPage === '' ? 'active' : ''}">🏠 Home</a></li>
            <li class="dropdown">
                <a href="bag.html" class="dropbtn ${currentPage === 'bag.html' ? 'active' : ''}">🎒 Bag</a>
                <div class="dropdown-content">
                    <a href="manual.html#receiptTab">1. ค้างออกใบรับ</a>
                    <a href="manual.html#issueTab">2. ค้างจ่าย</a>
                </div>
            </li>
            <li><a href="bigbag.html" class="${currentPage === 'bigbag.html' ? 'active' : ''}">📦 Big-Bag</a></li>
            <li><a href="pallet.html" class="${currentPage === 'pallet.html' ? 'active' : ''}">🪵 Pallet</a></li>
        </ul>
    </div>
    `;
});
