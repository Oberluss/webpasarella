<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        .sidebar {
            background-color: #343a40;
            min-height: 100vh;
            padding: 20px 0;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #495057;
            border-left: 3px solid #007bff;
        }
        .main-content {
            padding: 20px;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card h3 {
            font-size: 2rem;
            color: #007bff;
            margin: 0;
        }
        .order-status {
            padding: 5px 10px;
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
            padding: 3px 8px;
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
        .modal-header {
            background-color: #007bff;
            color: white;
        }
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -21px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #007bff;
        }
        .timeline-item::after {
            content: '';
            position: absolute;
            left: -15px;
            top: 17px;
            width: 2px;
            height: calc(100% - 12px);
            background-color: #dee2e6;
        }
        .timeline-item:last-child::after {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar">
                <h4 class="text-white text-center mb-4">Admin Panel</h4>
                <a href="admin-dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
                <a href="admin-users-full.php">
                    <i class="bi bi-people me-2"></i> Usuarios
                </a>
                <a href="admin-products.php">
                    <i class="bi bi-box-seam me-2"></i> Productos
                </a>
                <a href="admin-orders.php" class="active">
                    <i class="bi bi-cart3 me-2"></i> Pedidos
                </a>
                <hr class="bg-white">
                <a href="#" onclick="logout()">
                    <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                </a>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 main-content">
                <h2 class="mb-4">Gestión de Pedidos</h2>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h6 class="text-muted">Total Pedidos</h6>
                            <h3 id="totalOrders">0</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h6 class="text-muted">Pedidos Pendientes</h6>
                            <h3 id="pendingOrders" class="text-warning">0</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h6 class="text-muted">Ingresos Totales</h6>
                            <h3 id="totalRevenue">€0</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card">
                            <h6 class="text-muted">Pedidos Hoy</h6>
                            <h3 id="todayOrders">0</h3>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Estado del Pedido</label>
                                <select class="form-select" id="filterStatus">
                                    <option value="">Todos</option>
                                    <option value="pending">Pendiente</option>
                                    <option value="confirmed">Confirmado</option>
                                    <option value="processing">Procesando</option>
                                    <option value="shipped">Enviado</option>
                                    <option value="delivered">Entregado</option>
                                    <option value="cancelled">Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Estado de Pago</label>
                                <select class="form-select" id="filterPayment">
                                    <option value="">Todos</option>
                                    <option value="pending">Pendiente</option>
                                    <option value="paid">Pagado</option>
                                    <option value="failed">Fallido</option>
                                    <option value="refunded">Reembolsado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha Desde</label>
                                <input type="date" class="form-control" id="filterDateFrom">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha Hasta</label>
                                <input type="date" class="form-control" id="filterDateTo">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-primary" onclick="applyFilters()">
                                <i class="bi bi-funnel me-2"></i>Aplicar Filtros
                            </button>
                            <button class="btn btn-secondary" onclick="resetFilters()">
                                <i class="bi bi-arrow-clockwise me-2"></i>Limpiar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <table id="ordersTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nº Pedido</th>
                                    <th>Cliente</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Pago</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="ordersTableBody">
                                <!-- Orders will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-cart3 me-2"></i>
                        Detalles del Pedido
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Order Info -->
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Información del Pedido</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted">Número:</td>
                                    <td id="modalOrderNumber"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Fecha:</td>
                                    <td id="modalOrderDate"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Estado:</td>
                                    <td id="modalOrderStatus"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Pago:</td>
                                    <td id="modalPaymentStatus"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Método:</td>
                                    <td id="modalPaymentMethod"></td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Customer Info -->
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Información del Cliente</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td class="text-muted">Nombre:</td>
                                    <td id="modalCustomerName"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Email:</td>
                                    <td id="modalCustomerEmail"></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Teléfono:</td>
                                    <td id="modalCustomerPhone"></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Dirección de Envío</h6>
                            <div id="modalShippingAddress"></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Dirección de Facturación</h6>
                            <div id="modalBillingAddress"></div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <h6 class="fw-bold mt-4 mb-3">Productos del Pedido</h6>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unit.</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="modalOrderItems">
                            <!-- Items will be loaded here -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th id="modalOrderTotal"></th>
                            </tr>
                        </tfoot>
                    </table>

                    <!-- Notes -->
                    <div id="modalNotesSection" style="display: none;">
                        <h6 class="fw-bold mt-4 mb-3">Notas del Pedido</h6>
                        <p id="modalOrderNotes" class="text-muted"></p>
                    </div>

                    <!-- Update Status -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">Actualizar Estado</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <select class="form-select" id="updateOrderStatus">
                                        <option value="pending">Pendiente</option>
                                        <option value="confirmed">Confirmado</option>
                                        <option value="processing">Procesando</option>
                                        <option value="shipped">Enviado</option>
                                        <option value="delivered">Entregado</option>
                                        <option value="cancelled">Cancelado</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-primary w-100" onclick="updateOrderStatus()">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Actualizar Estado
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" onclick="printOrder()">
                        <i class="bi bi-printer me-2"></i>Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script>
        let ordersTable;
        let allOrders = [];
        let currentOrderId = null;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkAdminAuth();
            loadOrders();
            
            // Initialize DataTable
            ordersTable = $('#ordersTable').DataTable({
                order: [[2, 'desc']], // Sort by date descending
                pageLength: 25,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                }
            });
        });

        // Check admin authentication
        function checkAdminAuth() {
            const token = localStorage.getItem('adminToken');
            if (!token) {
                window.location.href = 'admin-login.php';
                return;
            }

            // Verify token is valid and user is admin
            fetch('api-proxy.php?path=auth/profile', {
                headers: {
                    'Authorization': 'Bearer ' + token
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success || data.user.role !== 'admin') {
                    localStorage.removeItem('adminToken');
                    window.location.href = 'admin-login.php';
                }
            })
            .catch(() => {
                localStorage.removeItem('adminToken');
                window.location.href = 'admin-login.php';
            });
        }

        // Load all orders
        function loadOrders() {
            fetch('api-proxy.php?path=orders/admin/all', {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('adminToken')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allOrders = data.orders;
                    displayOrders(allOrders);
                    updateStatistics(allOrders);
                }
            })
            .catch(error => {
                console.error('Error loading orders:', error);
                alert('Error al cargar los pedidos');
            });
        }

        // Display orders in table
        function displayOrders(orders) {
            ordersTable.clear();
            
            orders.forEach(order => {
                const statusBadge = `<span class="order-status status-${order.status}">${getStatusText(order.status)}</span>`;
                const paymentBadge = `<span class="payment-status payment-${order.payment_status}">${getPaymentStatusText(order.payment_status)}</span>`;
                const date = new Date(order.created_at).toLocaleDateString('es-ES');
                
                ordersTable.row.add([
                    order.order_number,
                    `${order.first_name} ${order.last_name}`,
                    date,
                    `€${parseFloat(order.total_amount).toFixed(2)}`,
                    statusBadge,
                    paymentBadge,
                    `
                        <button class="btn btn-sm btn-primary" onclick="viewOrder(${order.id})">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-success" onclick="quickUpdateStatus(${order.id}, 'shipped')">
                            <i class="bi bi-truck"></i>
                        </button>
                    `
                ]);
            });
            
            ordersTable.draw();
        }

        // Update statistics
        function updateStatistics(orders) {
            const today = new Date().toDateString();
            const todayOrders = orders.filter(o => new Date(o.created_at).toDateString() === today);
            const pendingOrders = orders.filter(o => o.status === 'pending');
            const totalRevenue = orders
                .filter(o => o.payment_status === 'paid')
                .reduce((sum, o) => sum + parseFloat(o.total_amount), 0);

            document.getElementById('totalOrders').textContent = orders.length;
            document.getElementById('pendingOrders').textContent = pendingOrders.length;
            document.getElementById('todayOrders').textContent = todayOrders.length;
            document.getElementById('totalRevenue').textContent = `€${totalRevenue.toFixed(2)}`;
        }

        // View order details
        function viewOrder(orderId) {
            currentOrderId = orderId;
            
            // Fetch order details
            fetch(`api-proxy.php?path=orders/${orderId}`, {
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('adminToken')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayOrderDetails(data.order);
                    const modal = new bootstrap.Modal(document.getElementById('orderModal'));
                    modal.show();
                }
            })
            .catch(error => {
                console.error('Error loading order details:', error);
                alert('Error al cargar los detalles del pedido');
            });
        }

        // Display order details in modal
        function displayOrderDetails(order) {
            // Basic info
            document.getElementById('modalOrderNumber').textContent = order.order_number;
            document.getElementById('modalOrderDate').textContent = new Date(order.created_at).toLocaleString('es-ES');
            document.getElementById('modalOrderStatus').innerHTML = 
                `<span class="order-status status-${order.status}">${getStatusText(order.status)}</span>`;
            document.getElementById('modalPaymentStatus').innerHTML = 
                `<span class="payment-status payment-${order.payment_status}">${getPaymentStatusText(order.payment_status)}</span>`;
            document.getElementById('modalPaymentMethod').textContent = getPaymentMethodText(order.payment_method);

            // Customer info - needs to be fetched from order
            const customerInfo = allOrders.find(o => o.id === order.id);
            if (customerInfo) {
                document.getElementById('modalCustomerName').textContent = 
                    `${customerInfo.first_name} ${customerInfo.last_name}`;
                document.getElementById('modalCustomerEmail').textContent = customerInfo.email;
            }

            // Addresses
            const shipping = order.shipping_address;
            document.getElementById('modalShippingAddress').innerHTML = `
                ${shipping.first_name} ${shipping.last_name}<br>
                ${shipping.address}<br>
                ${shipping.address2 ? shipping.address2 + '<br>' : ''}
                ${shipping.city}, ${shipping.state} ${shipping.zip}<br>
                ${getCountryName(shipping.country)}<br>
                Tel: ${shipping.phone}
            `;

            if (order.billing_address) {
                const billing = order.billing_address;
                document.getElementById('modalBillingAddress').innerHTML = `
                    ${billing.first_name} ${billing.last_name}<br>
                    ${billing.address}<br>
                    ${billing.address2 ? billing.address2 + '<br>' : ''}
                    ${billing.city}, ${billing.state} ${billing.zip}<br>
                    ${getCountryName(billing.country)}
                `;
            } else {
                document.getElementById('modalBillingAddress').innerHTML = 
                    '<span class="text-muted">Igual que dirección de envío</span>';
            }

            // Order items
            let itemsHtml = '';
            order.items.forEach(item => {
                itemsHtml += `
                    <tr>
                        <td>${item.product_name}</td>
                        <td>${item.quantity}</td>
                        <td>€${parseFloat(item.unit_price).toFixed(2)}</td>
                        <td>€${parseFloat(item.total_price).toFixed(2)}</td>
                    </tr>
                `;
            });
            document.getElementById('modalOrderItems').innerHTML = itemsHtml;
            document.getElementById('modalOrderTotal').textContent = 
                `€${parseFloat(order.total_amount).toFixed(2)}`;

            // Notes
            if (order.notes) {
                document.getElementById('modalNotesSection').style.display = 'block';
                document.getElementById('modalOrderNotes').textContent = order.notes;
            } else {
                document.getElementById('modalNotesSection').style.display = 'none';
            }

            // Set current status in select
            document.getElementById('updateOrderStatus').value = order.status;
            document.getElementById('modalCustomerPhone').textContent = shipping.phone || '-';
        }

        // Update order status
        function updateOrderStatus() {
            const newStatus = document.getElementById('updateOrderStatus').value;
            
            fetch(`api-proxy.php?path=orders/${currentOrderId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + localStorage.getItem('adminToken')
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Estado actualizado correctamente');
                    bootstrap.Modal.getInstance(document.getElementById('orderModal')).hide();
                    loadOrders();
                } else {
                    alert('Error al actualizar el estado');
                }
            })
            .catch(error => {
                console.error('Error updating status:', error);
                alert('Error al actualizar el estado');
            });
        }

        // Quick update status
        function quickUpdateStatus(orderId, status) {
            if (confirm(`¿Marcar pedido como ${getStatusText(status)}?`)) {
                fetch(`api-proxy.php?path=orders/${orderId}/status`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + localStorage.getItem('adminToken')
                    },
                    body: JSON.stringify({ status: status })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadOrders();
                    } else {
                        alert('Error al actualizar el estado');
                    }
                })
                .catch(error => {
                    console.error('Error updating status:', error);
                    alert('Error al actualizar el estado');
                });
            }
        }

        // Apply filters
        function applyFilters() {
            const status = document.getElementById('filterStatus').value;
            const payment = document.getElementById('filterPayment').value;
            const dateFrom = document.getElementById('filterDateFrom').value;
            const dateTo = document.getElementById('filterDateTo').value;

            let filtered = allOrders;

            if (status) {
                filtered = filtered.filter(o => o.status === status);
            }

            if (payment) {
                filtered = filtered.filter(o => o.payment_status === payment);
            }

            if (dateFrom) {
                filtered = filtered.filter(o => new Date(o.created_at) >= new Date(dateFrom));
            }

            if (dateTo) {
                const endDate = new Date(dateTo);
                endDate.setHours(23, 59, 59);
                filtered = filtered.filter(o => new Date(o.created_at) <= endDate);
            }

            displayOrders(filtered);
        }

        // Reset filters
        function resetFilters() {
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterPayment').value = '';
            document.getElementById('filterDateFrom').value = '';
            document.getElementById('filterDateTo').value = '';
            displayOrders(allOrders);
        }

        // Print order
        function printOrder() {
            window.print();
        }

        // Helper functions
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
                'card': 'Tarjeta',
                'paypal': 'PayPal',
                'transfer': 'Transferencia'
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

        // Logout
        function logout() {
            localStorage.removeItem('adminToken');
            window.location.href = 'admin-login.php';
        }
    </script>
</body>
</html>