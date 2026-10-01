/* ======================================================
   E-KANTIN INTERACTIVE JAVASCRIPT
   ====================================================== */

document.addEventListener('DOMContentLoaded', function () {
    
    // --- 1. LIVE SEARCH & CATEGORY FILTER FOR MENU ---
    const searchInput = document.getElementById('searchMenu');
    const categoryTabs = document.querySelectorAll('.category-tab');
    const menuCards = document.querySelectorAll('.menu-item-wrapper');

    let currentCategory = 'all';

    function filterMenu() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';

        menuCards.forEach(card => {
            const name = card.dataset.name ? card.dataset.name.toLowerCase() : '';
            const category = card.dataset.category ? card.dataset.category : '';

            const matchesSearch = name.includes(query);
            const matchesCategory = (currentCategory === 'all' || category === currentCategory);

            if (matchesSearch && matchesCategory) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterMenu);
    }

    if (categoryTabs.length > 0) {
        categoryTabs.forEach(tab => {
            tab.addEventListener('click', function () {
                categoryTabs.forEach(t => t.classList.remove('active', 'btn-primary'));
                categoryTabs.forEach(t => t.classList.add('btn-outline-light'));
                
                this.classList.remove('btn-outline-light');
                this.classList.add('active', 'btn-primary');

                currentCategory = this.dataset.category;
                filterMenu();
            });
        });
    }

    // --- 2. ORDER MODAL & QUANTITY CALCULATION ---
    const orderModal = document.getElementById('orderModal');
    const btnCloseModal = document.querySelectorAll('.btn-close-modal');
    
    const modalMenuId = document.getElementById('modal_id_menu');
    const modalNamaMenu = document.getElementById('modal_nama_menu');
    const modalKategoriMenu = document.getElementById('modal_kategori');
    const modalHargaText = document.getElementById('modal_harga_text');
    const modalStokText = document.getElementById('modal_stok_text');
    const inputJumlah = document.getElementById('modal_jumlah');
    const modalTotalText = document.getElementById('modal_total_text');
    const btnPesanSubmit = document.getElementById('btnPesanSubmit');
    const btnQtyMinus = document.getElementById('btnQtyMinus');
    const btnQtyPlus = document.getElementById('btnQtyPlus');

    let currentHarga = 0;
    let currentStok = 0;

    function formatRupiahJS(number) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number);
    }

    function updateTotalHarga() {
        if (!inputJumlah) return;
        let qty = parseInt(inputJumlah.value) || 1;

        if (qty < 1) qty = 1;
        if (qty > currentStok) qty = currentStok;

        inputJumlah.value = qty;
        const total = qty * currentHarga;
        if (modalTotalText) {
            modalTotalText.textContent = formatRupiahJS(total);
        }

        if (btnPesanSubmit) {
            if (currentStok <= 0 || qty > currentStok) {
                btnPesanSubmit.disabled = true;
                btnPesanSubmit.classList.add('opacity-50');
            } else {
                btnPesanSubmit.disabled = false;
                btnPesanSubmit.classList.remove('opacity-50');
            }
        }
    }

    // Open Order Modal buttons
    const btnOpenOrders = document.querySelectorAll('.btn-open-order');
    btnOpenOrders.forEach(btn => {
        btn.addEventListener('click', function () {
            const id = this.dataset.id;
            const nama = this.dataset.nama;
            const kategori = this.dataset.kategori;
            const harga = parseFloat(this.dataset.harga);
            const stok = parseInt(this.dataset.stok);

            if (modalMenuId) modalMenuId.value = id;
            if (modalNamaMenu) modalNamaMenu.textContent = nama;
            if (modalKategoriMenu) modalKategoriMenu.textContent = kategori;
            if (modalHargaText) modalHargaText.textContent = formatRupiahJS(harga);
            if (modalStokText) modalStokText.textContent = stok + ' Porsi';

            currentHarga = harga;
            currentStok = stok;

            if (inputJumlah) {
                inputJumlah.value = 1;
                inputJumlah.max = stok;
            }

            updateTotalHarga();

            if (orderModal) {
                orderModal.classList.add('active');
            }
        });
    });

    if (btnQtyMinus) {
        btnQtyMinus.addEventListener('click', function () {
            let val = parseInt(inputJumlah.value) || 1;
            if (val > 1) {
                inputJumlah.value = val - 1;
                updateTotalHarga();
            }
        });
    }

    if (btnQtyPlus) {
        btnQtyPlus.addEventListener('click', function () {
            let val = parseInt(inputJumlah.value) || 1;
            if (val < currentStok) {
                inputJumlah.value = val + 1;
                updateTotalHarga();
            }
        });
    }

    if (inputJumlah) {
        inputJumlah.addEventListener('input', updateTotalHarga);
    }

    // Close modals
    btnCloseModal.forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = this.closest('.modal-overlay');
            if (modal) modal.classList.remove('active');
        });
    });

    // Close modal on click outside
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.remove('active');
            }
        });
    });

    // --- 3. PRINT STRUK HANDLER ---
    const btnPrintStruk = document.getElementById('btnPrintStruk');
    if (btnPrintStruk) {
        btnPrintStruk.addEventListener('click', function () {
            window.print();
        });
    }
});
