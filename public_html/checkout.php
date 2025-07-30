<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - WebPasarella</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        .checkout-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
        }
        .summary-total {
            font-size: 1.25rem;
            font-weight: bold;
            border-top: 2px solid #333;
            margin-top: 10px;
            padding-top: 10px;
        }
        .payment-method {
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method:hover {
            border-color: #007bff;
        }
        .payment-method.selected {
            border-color: #007bff;
            background-color: #f0f8ff;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            display: flex;
            align-items: center;
            color: #6c757d;
        }
        .step.active {
            color: #007bff;
        }
        .step.completed {
            color: #28a745;
        }
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 2px solid currentColor;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
            font-weight: bold;
        }
        .step-line {
            width: 100px;
            height: 2px;
            background-color: #ddd;
            margin: 0 20px;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .loading-spinner {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Procesando...</span>
            </div>
            <p>Procesando tu pedido...</p>
        </div>
    </div>

    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">WebPasarella</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text text-white" id="userInfo"></span>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step completed">
                <div class="step-number">1</div>
                <span>Carrito</span>
            </div>
            <div class="step-line"></div>
            <div class="step active">
                <div class="step-number">2</div>
                <span>Checkout</span>
            </div>
            <div class="step-line"></div>
            <div class="step">
                <div class="step-number">3</div>
                <span>Confirmación</span>
            </div>
        </div>

        <div class="row">
            <!-- Left Column - Forms -->
            <div class="col-lg-8">
                <!-- Shipping Information -->
                <div class="checkout-section">
                    <h4 class="mb-4">
                        <i class="bi bi-truck me-2"></i>
                        Información de Envío
                    </h4>
                    <form id="shippingForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="shipFirstName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellidos</label>
                                <input type="text" class="form-control" id="shipLastName" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="shipAddress" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dirección 2 (Opcional)</label>
                            <input type="text" class="form-control" id="shipAddress2">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ciudad</label>
                                <input type="text" class="form-control" id="shipCity" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Provincia</label>
                                <input type="text" class="form-control" id="shipState" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Código Postal</label>
                                <input type="text" class="form-control" id="shipZip" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">País</label>
                            <select class="form-select" id="shipCountry" required>
                                <option value="ES">España</option>
                                <option value="FR">Francia</option>
                                <option value="PT">Portugal</option>
                                <option value="IT">Italia</option>
                                <option value="DE">Alemania</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="shipPhone" required>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="sameAsBilling">
                            <label class="form-check-label" for="sameAsBilling">
                                La dirección de facturación es la misma que la de envío
                            </label>
                        </div>
                    </form>
                </div>

                <!-- Billing Information -->
                <div class="checkout-section" id="billingSection" style="display: none;">
                    <h4 class="mb-4">
                        <i class="bi bi-receipt me-2"></i>
                        Información de Facturación
                    </h4>
                    <form id="billingForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="billFirstName">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Apellidos</label>
                                <input type="text" class="form-control" id="billLastName">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="billAddress">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dirección 2 (Opcional)</label>
                            <input type="text" class="form-control" id="billAddress2">
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Ciudad</label>
                                <input type="text" class="form-control" id="billCity">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Provincia</label>
                                <input type="text" class="form-control" id="billState">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Código Postal</label>
                                <input type="text" class="form-control" id="billZip">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">País</label>
                            <select class="form-select" id="billCountry">
                                <option value="ES">España</option>
                                <option value="FR">Francia</option>
                                <option value="PT">Portugal</option>
                                <option value="IT">Italia</option>
                                <option value="DE">Alemania</option>
                            </select>
                        </div>
                    </form>
                </div>

                <!-- Payment Method -->
                <div class="checkout-section">
                    <h4 class="mb-4">
                        <i class="bi bi-credit-card me-2"></i>
                        Método de Pago
                    </h4>
                    <div class="payment-method" data-method="card" onclick="selectPaymentMethod('card')">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-credit-card fs-3 me-3"></i>
                            <div>
                                <h6 class="mb-0">Tarjeta de Crédito/Débito</h6>
                                <small class="text-muted">Pago seguro con tarjeta</small>
                            </div>
                        </div>
                    </div>
                    <div class="payment-method" data-method="paypal" onclick="selectPaymentMethod('paypal')">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-paypal fs-3 me-3"></i>
                            <div>
                                <h6 class="mb-0">PayPal</h6>
                                <small class="text-muted">Pago rápido y seguro con PayPal</small>
                            </div>
                        </div>
                    </div>
                    <div class="payment-method" data-method="transfer" onclick="selectPaymentMethod('transfer')">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-bank fs-3 me-3"></i>
                            <div>
                                <h6 class="mb-0">Transferencia Bancaria</h6>
                                <small class="text-muted">Transferencia manual (2-3 días)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="checkout-section">
                    <h4 class="mb-4">
                        <i class="bi bi-chat-left-text me-2"></i>
                        Notas del Pedido (Opcional)
                    </h4>
                    <textarea class="form-control" id="orderNotes" rows="3" 
                        placeholder="Notas sobre tu pedido, instrucciones de entrega, etc."></textarea>
                </div>
            </div>

            <!-- Right Column - Order Summary -->
            <div class="col-lg-4">
                <div class="checkout-section sticky-top" style="top: 20px;">
                    <h4 class="mb-4">
                        <i class="bi bi-bag-check me-2"></i>
                        Resumen del Pedido
                    </h4>
                    <div id="orderSummary">
                        <!-- Cart items will be loaded here -->
                    </div>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="subtotal">€0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Envío</span>
                        <span id="shipping">€5.00</span>
                    </div>
                    <div class="summary-row">
                        <span>IVA (21%)</span>
                        <span id="tax">€0.00</span>
                    </div>
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span id="total">€0.00</span>
                    </div>
                    <button class="btn btn-primary btn-lg w-100 mt-4" onclick="processOrder()">
                        <i class="bi bi-lock-fill me-2"></i>
                        Completar Pedido
                    </button>
                    <p class="text-center text-muted mt-3 small">
                        <i class="bi bi-shield-lock me-1"></i>
                        Pago 100% seguro
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedPaymentMethod = null;
        let cart = [];
        let user = null;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            checkAuth();
            loadCart();
            
            // Handle billing address toggle
            document.getElementById('sameAsBilling').addEventListener('change', function() {
                document.getElementById('billingSection').style.display = 
                    this.checked ? 'none' : 'block';
            });
        });

        // Check authentication
        function checkAuth() {
            const token = localStorage.getItem('token');
            if (!token) {
                alert('Debes iniciar sesión para continuar');
                window.location.href = 'index.php';
                return;
            }

            // Get user info
            fetch('api-proxy.php?path=auth/profile', {
                headers: {
                    'Authorization': 'Bearer ' + token
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    user = data.user;
                    document.getElementById('userInfo').textContent = 
                        `${user.first_name} ${user.last_name}`;
                    
                    // Pre-fill form with user data
                    document.getElementById('shipFirstName').value = user.first_name;
                    document.getElementById('shipLastName').value = user.last_name;
                    document.getElementById('shipPhone').value = user.phone || '';
                } else {
                    localStorage.removeItem('token');
                    window.location.href = 'index.php';
                }
            });
        }

        // Load cart items
        function loadCart() {
            cart = JSON.parse(localStorage.getItem('cart') || '[]');
            
            if (cart.length === 0) {
                alert('Tu carrito está vacío');
                window.location.href = 'index.php';
                return;
            }

            displayOrderSummary();
        }

        // Display order summary
        function displayOrderSummary() {
            const summaryDiv = document.getElementById('orderSummary');
            let subtotal = 0;
            let html = '';

            cart.forEach(item => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                
                html += `
                    <div class="cart-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">${item.name}</h6>
                                <small class="text-muted">Cantidad: ${item.quantity}</small>
                            </div>
                            <div class="text-end">
                                <span>€${itemTotal.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                `;
            });

            summaryDiv.innerHTML = html;

            // Calculate totals
            const shipping = 5.00;
            const tax = subtotal * 0.21;
            const total = subtotal + shipping + tax;

            document.getElementById('subtotal').textContent = `€${subtotal.toFixed(2)}`;
            document.getElementById('tax').textContent = `€${tax.toFixed(2)}`;
            document.getElementById('total').textContent = `€${total.toFixed(2)}`;
        }

        // Select payment method
        function selectPaymentMethod(method) {
            selectedPaymentMethod = method;
            
            // Update UI
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            document.querySelector(`[data-method="${method}"]`).classList.add('selected');
        }

        // Validate forms
        function validateForms() {
            const shippingForm = document.getElementById('shippingForm');
            if (!shippingForm.checkValidity()) {
                shippingForm.reportValidity();
                return false;
            }

            const sameAsBilling = document.getElementById('sameAsBilling').checked;
            if (!sameAsBilling) {
                const billingForm = document.getElementById('billingForm');
                if (!billingForm.checkValidity()) {
                    billingForm.reportValidity();
                    return false;
                }
            }

            if (!selectedPaymentMethod) {
                alert('Por favor selecciona un método de pago');
                return false;
            }

            return true;
        }

        // Get form data
        function getFormData() {
            const shippingAddress = {
                first_name: document.getElementById('shipFirstName').value,
                last_name: document.getElementById('shipLastName').value,
                address: document.getElementById('shipAddress').value,
                address2: document.getElementById('shipAddress2').value,
                city: document.getElementById('shipCity').value,
                state: document.getElementById('shipState').value,
                zip: document.getElementById('shipZip').value,
                country: document.getElementById('shipCountry').value,
                phone: document.getElementById('shipPhone').value
            };

            let billingAddress = null;
            if (!document.getElementById('sameAsBilling').checked) {
                billingAddress = {
                    first_name: document.getElementById('billFirstName').value,
                    last_name: document.getElementById('billLastName').value,
                    address: document.getElementById('billAddress').value,
                    address2: document.getElementById('billAddress2').value,
                    city: document.getElementById('billCity').value,
                    state: document.getElementById('billState').value,
                    zip: document.getElementById('billZip').value,
                    country: document.getElementById('billCountry').value
                };
            }

            return {
                shipping_address: shippingAddress,
                billing_address: billingAddress,
                payment_method: selectedPaymentMethod,
                notes: document.getElementById('orderNotes').value
            };
        }

        // Process order
        function processOrder() {
            if (!validateForms()) {
                return;
            }

            const formData = getFormData();
            const orderData = {
                items: cart.map(item => ({
                    product_id: item.id,
                    quantity: item.quantity
                })),
                ...formData
            };

            // Show loading
            document.getElementById('loadingOverlay').style.display = 'flex';

            // Send order to API
            fetch('api-proxy.php?path=orders', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + localStorage.getItem('token')
                },
                body: JSON.stringify(orderData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear cart
                    localStorage.removeItem('cart');
                    
                    // Redirect to success page or payment gateway
                    if (selectedPaymentMethod === 'card' || selectedPaymentMethod === 'paypal') {
                        // Here you would redirect to payment gateway
                        // For now, we'll just show success
                        alert(`Pedido ${data.order.order_number} creado exitosamente!\n\nAquí redirigiríamos a la pasarela de pago.`);
                        window.location.href = 'index.php';
                    } else {
                        // Transfer - show bank details
                        alert(`Pedido ${data.order.order_number} creado exitosamente!\n\nTe enviaremos los datos bancarios por email.`);
                        window.location.href = 'index.php';
                    }
                } else {
                    throw new Error(data.error || 'Error al procesar el pedido');
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            })
            .finally(() => {
                document.getElementById('loadingOverlay').style.display = 'none';
            });
        }
    </script>
</body>
</html>