<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos - WebPasarella</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .order-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: box-shadow 0.3s;
        }
        .order-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .order-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pending { 
            background-color: #ffc107; 
            color: #000; 
        }
        .status-confirmed { 
            background-color: #17a2b8; 
            color: #fff; 
        }
        .status-processing { 
            background-color: #6c757d; 
            color: #fff; 
        }
        .status-shipped { 
            background-color: #007bff; 
            color: #fff; 
        }
        .status-delivered { 
            background-color: #28a745; 
            color: #fff; 
        }
        .status-cancelled { 
            background-color: #dc3545; 
            color: #fff; 
        }
        .payment-status {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
        }
        .payment-pending { 
            background-color: #fff3cd; 
            color: #856404; 
        }
        .payment-paid { 
            background-color: #d4edda; 
            color: #155724; 
        }
        .payment-failed { 
            background-color: #f8d7da; 
            color: #721c24; 
        }
        .order-timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            position: relative;
        }
        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e9ecef;
            color: #6c757d;
            z-index: 2;
        }
        .timeline-icon.active {
            background-color: #007bff;
            color: white;
        }
        .timeline-icon.completed {
            background-color: #28a745;
            color: white;
        }
        .timeline-content {
            margin-left: 20px;
        }
        .timeline-line {
            position: absolute;
            left: 20px;
            top: 40px;
            bottom: 0;
            width: 2px;
            background-color: #dee2e6;
            z-index: 1;
        }
        .timeline-item:last-child .timeline-line {
            display: none;
        }
        .empty-orders {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-orders i {
            font-size: 4rem;
            color: #dee2e6;
        }
        .filter-tabs {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 30px;
        }
        .filter-tab {
            padding: 10px 20px;
            border: none;
            background: none;
            color: #6c757d;
            font-weight: 500;
            position: relative;
            transition: color 0.3s;
        }
        .filter-tab:hover {
            color: #007bff;
        }
        .filter-tab.active {
            color: #007bff;
        }
        .filter-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop me-2"></i>WebPasarella
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#products">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contacto/index.php">Contacto</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="bi bi-cart3"></i>
                            <span class="badge bg-danger" id="cartCount" style="display: none;">0</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <span id="userName"></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="viewProfile()">Mi Perfil</a></li>
                            <li><a class="dropdown-item active" href="my-orders.php">Mis Pedidos</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout()">Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <h2 class="mb-4">
            <i class="bi bi-bag-check me-2"></i>
            Mis Pedidos
        </h2>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <button class="filter-tab active" onclick="filterOrders('all')">
                Todos
                <span class="badge bg-secondary ms-1" id="countAll">0</span>
            </button>
            <button class="filter-tab" onclick="filterOrders('pending')">
                Pendientes
                <span class="badge bg-warning ms-1" id="countPending">0</span>
            </button>
            <button class="filter-tab" onclick="filterOrders('processing')">
                En Proceso
                <span class="badge bg-info ms-1" id="countProcessing">0</span>
            </button>
            <button class="filter-tab" onclick="filterOrders('delivered')">
                Entregados
                <span class="badge bg-success ms-1" id="countDelivered">0</span>
            </button>
            <button class="filter-tab" onclick="filterOrders('cancelled')">
                Cancelados
                <span class="badge bg-danger ms-1" id="countCancelled">0</span>
            </button>
        </div>

        <!-- Orders Container -->
        <div id="ordersContainer">
            <!-- Orders will be loaded here -->
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-receipt me-2"></i>
                        Detalles del Pedido
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    <!-- Order details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-danger" id="cancelOrderBtn" onclick="cancelOrder()" style="display: none;">
                        <i class="bi bi-x-circle me-2"></i>Cancelar Pedido
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p>&copy; 2025 WebPasarella. Todos los derechos reservados.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let allOrders = [];
        let currentFilter = 'all';
        let currentOrderId = null;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkAuth();
            updateCartCount();
        });

        // Check authentication
        function checkAuth() {
            const token = localStorage.getItem('token');
            if (!token) {
                window.location.href = 'index.php';
                return;
            }

            // Get user profile and orders
            fetch('api-proxy.php?path=auth/profile', {
                headers: {
                    'Authorization': 'Bearer ' + token
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('userName').textContent = data.user.first_name;
                    loadOrders();
                } else {
                    localStorage.removeItem('token');
                    window.location.href = 'index.php';
                }
            });
        }

        // Load user orders
        function loadOrders() {
            fetch('api-proxy.php?path=orders', {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allOrders = data.orders;
                    updateCounts();
                    displayOrders();
                }
            })
            .catch(error => {
                console.error('Error loading orders:', error);
            });
        }

        // Update filter counts
        function updateCounts() {
            const counts = {
                all: allOrders.length,
                pending: allOrders.filter(o => o.status === 'pending').length,
                processing: allOrders.filter(o => ['confirmed', 'processing', 'shipped'].includes(o.status)).length,
                delivered: allOrders.filter(o => o.status === 'delivered').length,
                cancelled: allOrders.filter(o => o.status === 'cancelled').length
            };

            document.getElementById('countAll').textContent = counts.all;
            document.getElementById('countPending').textContent = counts.pending;
            document.getElementById('countProcessing').textContent = counts.processing;
            document.getElementById('countDelivered').textContent = counts.delivered;
            document.getElementById('countCancelled').textContent = counts.cancelled;
        }

        // Filter orders
        function filterOrders(filter) {
            currentFilter = filter;
            
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            displayOrders();
        }

        // Display orders
        function displayOrders() {
            let filteredOrders = allOrders;
            
            if (currentFilter !== 'all') {
                if (currentFilter === 'processing') {
                    filteredOrders = allOrders.filter(o => 
                        ['confirmed', 'processing', 'shipped'].includes(o.status)
                    );
                } else {
                    filteredOrders = allOrders.filter(o => o.status === currentFilter);
                }
            }

            const container = document.getElementById('ordersContainer');
            
            if (filteredOrders.length === 0) {
                container.innerHTML = `
                    <div class="empty-orders">
                        <i class="bi bi-bag-x"></i>
                        <h4 class="mt-3">No hay pedidos ${getFilterText()}</h4>
                        <p class="text-muted">Cuando realices una compra, aparecerá aquí</p>
                        <a href="index.php" class="btn btn-primary mt-3">
                            <i class="bi bi-shop me-2"></i>Ir de Compras
                        </a>
                    </div>
                `;
                return;
            }

            let html = '';
            filteredOrders.forEach(order => {
                const date = new Date(order.created_at);
                const formattedDate = date.toLocaleDateString('es-ES', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric'
                });
                
                html += `
                    <div class="order-card">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="mb-1">Pedido #${order.order_number}</h5>
                                <p class="text-muted mb-2">
                                    <i class="bi bi-calendar me-1"></i>${formattedDate}
                                    <span class="mx-2">|</span>
                                    <i class="bi bi-box me-1"></i>${order.total_items} producto(s)
                                </p>
                                <div class="mb-2">
                                    <span class="order-status status-${order.status}">
                                        ${getStatusText(order.status)}
                                    </span>
                                    <span class="payment-status payment-${order.payment_status} ms-2">
                                        Pago ${getPaymentStatusText(order.payment_status)}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <h4 class="text-primary mb-2">€${parseFloat(order.total_amount).toFixed(2)}</h4>
                                <button class="btn btn-outline-primary btn-sm" onclick="viewOrderDetails(${order.id})">
                                    <i class="bi bi-eye me-1"></i>Ver Detalles
                                </button>
                            </div>
                        </div>
                        ${getOrderTimeline(order.status)}
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Get order timeline
        function getOrderTimeline(status) {
            const steps = [
                { key: 'pending', icon: 'clock', text: 'Pendiente' },
                { key: 'confirmed', icon: 'check-circle', text: 'Confirmado' },
                { key: 'processing', icon: 'box-seam', text: 'Preparando' },
                { key: 'shipped', icon: 'truck', text: 'Enviado' },
                { key: 'delivered', icon: 'house-check', text: 'Entregado' }
            ];

            const statusIndex = steps.findIndex(s => s.key === status);
            
            let html = '<div class="order-timeline mt-3">';
            
            steps.forEach((step, index) => {
                let iconClass = '';
                if (index < statusIndex) iconClass = 'completed';
                else if (index === statusIndex) iconClass = 'active';
                
                html += `
                    <div class="timeline-item">
                        <div class="timeline-icon ${iconClass}">
                            <i class="bi bi-${step.icon}"></i>
                        </div>
                        <div class="timeline-content">
                            <small class="${iconClass ? 'fw-bold' : 'text-muted'}">${step.text}</small>
                        </div>
                        ${index < steps.length - 1 ? '<div class="timeline-line"></div>' : ''}
                    </div>
                `;
            });
            
            html += '</div>';
            
            return status === 'cancelled' ? '' : html;
        }

        // View order details
        function viewOrderDetails(orderId) {
            currentOrderId = orderId;
            
            fetch(`api-proxy.php?path=orders/${orderId}`, {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayOrderDetails(data.order);
                    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
                    modal.show();
                }
            });
        }

        // Display order details
        function displayOrderDetails(order) {
            const date = new Date(order.created_at);
            const shipping = order.shipping_address;
            
            let itemsHtml = '';
            order.items.forEach(item => {
                itemsHtml += `
                    <tr>
                        <td>${item.product_name}</td>
                        <td class="text-center">${item.quantity}</td>
                        <td class="text-end">€${parseFloat(item.unit_price).toFixed(2)}</td>
                        <td class="text-end">€${parseFloat(item.total_price).toFixed(2)}</td>
                    </tr>
                `;
            });

            const html = `
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="fw-bold">Información del Pedido</h6>
                        <p class="mb-1"><strong>Número:</strong> ${order.order_number}</p>
                        <p class="mb-1"><strong>Fecha:</strong> ${date.toLocaleString('es-ES')}</p>
                        <p class="mb-1"><strong>Estado:</strong> 
                            <span class="order-status status-${order.status}">
                                ${getStatusText(order.status)}
                            </span>
                        </p>
                        <p class="mb-1"><strong>Método de Pago:</strong> ${getPaymentMethodText(order.payment_method)}</p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold">Dirección de Envío</h6>
                        <p class="mb-0">
                            ${shipping.first_name} ${shipping.last_name}<br>
                            ${shipping.address}<br>
                            ${shipping.city}, ${shipping.state} ${shipping.zip}<br>
                            ${getCountryName(shipping.country)}<br>
                            Tel: ${shipping.phone}
                        </p>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Productos</h6>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-end">Precio Unit.</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="3" class="text-end">Total:</td>
                            <td class="text-end">€${parseFloat(order.total_amount).toFixed(2)}</td>
                        </tr>
                    </tfoot>
                </table>

                ${order.notes ? `
                    <div class="mt-3">
                        <h6 class="fw-bold">Notas del Pedido</h6>
                        <p class="text-muted">${order.notes}</p>
                    </div>
                ` : ''}
            `;

            document.getElementById('orderDetailsContent').innerHTML = html;
            
            // Show/hide cancel button
            const cancelBtn = document.getElementById('cancelOrderBtn');
            if (order.status === 'pending') {
                cancelBtn.style.display = 'inline-block';
            } else {
                cancelBtn.style.display = 'none';
            }
        }

        // Cancel order
        function cancelOrder() {
            if (!confirm('¿Estás seguro de que quieres cancelar este pedido?')) {
                return;
            }

            fetch(`api-proxy.php?path=orders/${currentOrderId}/cancel`, {
                method: 'PUT',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Pedido cancelado exitosamente');
                    bootstrap.Modal.getInstance(document.getElementById('orderDetailsModal')).hide();
                    loadOrders();
                } else {
                    alert(data.error || 'Error al cancelar el pedido');
                }
            });
        }

        // Helper functions
        function getFilterText() {
            const texts = {
                all: '',
                pending: 'pendientes',
                processing: 'en proceso',
                delivered: 'entregados',
                cancelled: 'cancelados'
            };
            return texts[currentFilter] || '';
        }

        function getStatusText(status) {
            const statuses = {
                'pending': 'Pendiente',
                'confirmed': 'Confirmado',
                'processing': 'Procesando',
                'shipped': 'Enviado',
                'delivered': 'Entregado',
                'cancelled': 'Cancelado'
            };
            return statuses[status] || status;
        }

        function getPaymentStatusText(status) {
            const statuses = {
                'pending': 'Pendiente',
                'paid': 'Pagado',
                'failed': 'Fallido',
                'refunded': 'Reembolsado'
            };
            return statuses[status] || status;
        }

        function getPaymentMethodText(method) {
            const methods = {
                'card': 'Tarjeta de Crédito',
                'paypal': 'PayPal',
                'transfer': 'Transferencia Bancaria'
            };
            return methods[method] || method;
        }

        function getCountryName(code) {
            const countries = {
                'ES': 'España',
                'FR': 'Francia',
                'PT': 'Portugal',
                'IT': 'Italia',
                'DE': 'Alemania'
            };
            return countries[code] || code;
        }

        // Update cart count
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart') || '[]');
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            const badge = document.getElementById('cartCount');
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'inline' : 'none';
            }
        }

        // Navigation functions
        function viewProfile() {
            alert('Página de perfil - Por implementar');
        }

        function logout() {
            localStorage.removeItem('token');
            window.location.href = 'index.php';
        }
    </script>
</body>
</html>