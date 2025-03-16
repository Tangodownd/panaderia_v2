<!DOCTYPE html>
<html>
<head>
    <title>Depuración de Vue</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            border: 1px solid #ddd;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .product {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 10px;
            border: 1px solid #eee;
        }
        .product img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-right: 15px;
        }
        .product-info {
            flex-grow: 1;
        }
        .cart-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .item-details {
            display: flex;
            align-items: center;
        }
        .item-image {
            width: 50px;
            height: 50px;
            margin-right: 10px;
            object-fit: cover;
        }
        .item-quantity {
            display: flex;
            align-items: center;
        }
        .item-quantity button {
            padding: 5px 10px;
            margin: 0 5px;
        }
        button {
            padding: 8px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            overflow: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Depuración de Vue y Carrito</h1>
        
        <div id="app">
            <div class="card">
                <h2>Información de Vue</h2>
                <p>Versión de Vue: {{ vueVersion }}</p>
                <p>Vue cargado correctamente: {{ vueLoaded ? 'Sí' : 'No' }}</p>
            </div>
            
            <div class="card">
                <h2>Productos</h2>
                <div v-if="loading">Cargando productos...</div>
                <div v-else-if="error">Error: {{ error }}</div>
                <div v-else>
                    <div v-for="product in products" :key="product.id" class="product">
                        <img :src="'/storage/' + product.image" :alt="product.name" v-if="product.image">
                        <div class="product-info">
                            <h3>{{ product.name }}</h3>
                            <p>{{ product.description }}</p>
                            <p>Precio: ${{ product.price }}</p>
                        </div>
                        <button @click="addToCart(product)">Añadir al carrito</button>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2>Carrito de Compras</h2>
                <div v-if="cartLoading">Cargando carrito...</div>
                <div v-else-if="cartError">Error: {{ cartError }}</div>
                <div v-else-if="cartItems.length === 0">
                    <p>Tu carrito está vacío</p>
                </div>
                <div v-else>
                    <div v-for="item in cartItems" :key="item.product_id" class="cart-item">
                        <div class="item-details">
                            <img v-if="item.product && item.product.image" :src="'/storage/' + item.product.image" :alt="item.product.name" class="item-image">
                            <div>
                                <h4>{{ item.product ? item.product.name : item.name }}</h4>
                                <p>Precio: ${{ item.price }}</p>
                            </div>
                        </div>
                        <div class="item-quantity">
                            <button @click="updateQuantity(item, item.quantity - 1)">-</button>
                            <span>{{ item.quantity }}</span>
                            <button @click="updateQuantity(item, item.quantity + 1)">+</button>
                        </div>
                        <div>${{ (item.quantity * parseFloat(item.price)).toFixed(2) }}</div>
                        <button @click="removeFromCart(item)">×</button>
                    </div>
                    <div style="text-align: right; margin-top: 15px;">
                        <h3>Total: ${{ cartTotal.toFixed(2) }}</h3>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <h2>Depuración</h2>
                <button @click="fetchCart">Recargar carrito</button>
                <button @click="fetchProducts">Recargar productos</button>
                <button @click="clearCart">Limpiar carrito</button>
                
                <h3>Respuesta de la API (Carrito)</h3>
                <pre>{{ JSON.stringify(cartResponse, null, 2) }}</pre>
                
                <h3>Cookies</h3>
                <pre>{{ cookies }}</pre>
            </div>
        </div>
    </div>
    
    <script>
        // Configurar Axios
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        axios.defaults.withCredentials = true;
        
        const token = document.head.querySelector('meta[name="csrf-token"]');
        if (token) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
        }
        
        new Vue({
            el: '#app',
            data: {
                vueVersion: Vue.version,
                vueLoaded: !!Vue,
                products: [],
                loading: true,
                error: null,
                cartItems: [],
                cartLoading: true,
                cartError: null,
                cartResponse: null,
                cookies: document.cookie
            },
            computed: {
                cartTotal() {
                    return this.cartItems.reduce((sum, item) => {
                        return sum + (item.quantity * parseFloat(item.price));
                    }, 0);
                }
            },
            mounted() {
                this.fetchProducts();
                this.fetchCart();
            },
            methods: {
                fetchProducts() {
                    this.loading = true;
                    this.error = null;
                    
                    axios.get('/api/products')
                        .then(response => {
                            this.products = response.data;
                            this.loading = false;
                        })
                        .catch(error => {
                            this.error = error.message;
                            this.loading = false;
                            console.error('Error al cargar productos:', error);
                        });
                },
                
                fetchCart() {
                    this.cartLoading = true;
                    this.cartError = null;
                    
                    axios.get('/api/cart')
                        .then(response => {
                            this.cartResponse = response.data;
                            this.cartItems = response.data.items;
                            this.cartLoading = false;
                            this.cookies = document.cookie;
                        })
                        .catch(error => {
                            this.cartError = error.message;
                            this.cartLoading = false;
                            console.error('Error al cargar carrito:', error);
                        });
                },
                
                addToCart(product) {
                    axios.post('/api/cart/add', {
                        product_id: product.id,
                        quantity: 1
                    })
                        .then(response => {
                            this.cartResponse = response.data;
                            this.cartItems = response.data.items;
                            this.cookies = document.cookie;
                        })
                        .catch(error => {
                            console.error('Error al añadir producto:', error);
                        });
                },
                
                removeFromCart(item) {
                    axios.post('/api/cart/remove', {
                        product_id: item.product_id
                    })
                        .then(response => {
                            this.cartResponse = response.data;
                            this.cartItems = response.data.items;
                        })
                        .catch(error => {
                            console.error('Error al eliminar producto:', error);
                        });
                },
                
                updateQuantity(item, quantity) {
                    if (quantity <= 0) {
                        this.removeFromCart(item);
                        return;
                    }
                    
                    axios.post('/api/cart/update', {
                        product_id: item.product_id,
                        quantity: quantity
                    })
                        .then(response => {
                            this.cartResponse = response.data;
                            this.cartItems = response.data.items;
                        })
                        .catch(error => {
                            console.error('Error al actualizar cantidad:', error);
                        });
                },
                
                clearCart() {
                    if (confirm('¿Estás seguro de que quieres vaciar el carrito?')) {
                        axios.post('/api/cart/clear')
                            .then(response => {
                                this.cartResponse = response.data;
                                this.cartItems = [];
                            })
                            .catch(error => {
                                console.error('Error al vaciar carrito:', error);
                            });
                    }
                }
            }
        });
    </script>
</body>
</html>

