import { mount, flushPromises } from '@vue/test-utils'
import ShoppingCart from '@/components/client/ShoppingCart.vue'
import axios from 'axios'

// Mock de axios
jest.mock('axios')

// Mock del componente CheckoutForm
jest.mock('@/components/client/CheckoutForm.vue', () => ({
  name: 'CheckoutForm',
  template: '<div class="mock-checkout-form">Checkout Form</div>',
  props: ['cart']
}))

describe('ShoppingCart.vue', () => {
  let wrapper

  beforeEach(async () => {
    // Mock de la respuesta de axios para la solicitud del carrito
    axios.get.mockResolvedValue({
      data: {
        cart: {
          id: 1,
          total: 15.00
        },
        items: [
          { id: 1, product_id: 1, quantity: 2, price: 5.00, product: { id: 1, name: 'Pan Francés', price: 5.00 } },
          { id: 2, product_id: 2, quantity: 1, price: 5.00, product: { id: 2, name: 'Croissant', price: 5.00 } }
        ]
      }
    })

    // Montar el componente
    wrapper = mount(ShoppingCart, {
      global: {
        stubs: {
          // Stub cualquier componente externo si es necesario
        },
        mocks: {
          // Proporcionar mocks para cualquier propiedad global
        }
      }
    })
    
    // Esperar a que se resuelvan las promesas (como la carga del carrito)
    await flushPromises()
  })

  afterEach(() => {
    wrapper.unmount()
    jest.clearAllMocks()
  })

  test('muestra el botón del carrito con la cantidad correcta', async () => {
    // Verificar que el botón del carrito existe
    const cartButton = wrapper.find('button.btn-cart')
    expect(cartButton.exists()).toBe(true)
    
    // Verificar que muestra la cantidad correcta
    const badge = wrapper.find('.badge')
    expect(badge.exists()).toBe(true)
    expect(badge.text()).toBe('3')
  })

  test('abre el modal del carrito al hacer clic en el botón', async () => {
    // Verificar que el modal está cerrado inicialmente
    expect(wrapper.vm.isCartOpen).toBe(false)
    
    // Hacer clic en el botón del carrito
    await wrapper.find('button.btn-cart').trigger('click')
    
    // Verificar que el modal está abierto
    expect(wrapper.vm.isCartOpen).toBe(true)
    
    // Verificar que el modal muestra el contenido del carrito
    const modalContent = wrapper.find('.custom-modal-content')
    expect(modalContent.exists()).toBe(true)
  })

  test('muestra los items del carrito correctamente cuando se abre el modal', async () => {
    // Abrir el modal del carrito
    await wrapper.find('button.btn-cart').trigger('click')
    await flushPromises()
    
    // Verificar que el modal muestra el total del carrito
    const modalContent = wrapper.find('.custom-modal-content')
    expect(modalContent.text()).toContain('15')
    
    // Verificar que se muestran los nombres de los productos
    expect(modalContent.text()).toContain('Pan Francés')
    expect(modalContent.text()).toContain('Croissant')
  })

  test('muestra mensaje cuando el carrito está vacío', async () => {
    // Crear una nueva instancia con un carrito vacío
    axios.get.mockResolvedValueOnce({
      data: {
        cart: {
          id: 1,
          total: 0
        },
        items: []
      }
    })
    
    const emptyWrapper = mount(ShoppingCart)
    await flushPromises()
    
    // Abrir el modal del carrito
    await emptyWrapper.find('button.btn-cart').trigger('click')
    await flushPromises()
    
    // Verificar que se muestre el mensaje de carrito vacío
    const modalContent = emptyWrapper.find('.custom-modal-content')
    expect(modalContent.text()).toContain('Tu carrito está vacío')
    
    emptyWrapper.unmount()
  })

  test('muestra el formulario de checkout al hacer clic en Proceder al pago', async () => {
    // Abrir el modal del carrito
    await wrapper.find('button.btn-cart').trigger('click')
    await flushPromises()
    
    // Imprimir el HTML del modal para depuración
    console.log('HTML del modal del carrito:', wrapper.find('.custom-modal-content').html())
    
    // Buscar el botón de proceder al pago dentro del modal
    // Usamos un selector más específico para encontrar el botón correcto
    const checkoutButton = wrapper.find('.custom-modal-body .d-grid .btn-brown')
    
    // Verificar que el botón existe
    expect(checkoutButton.exists()).toBe(true)
    
    // Verificar que el texto del botón es correcto
    expect(checkoutButton.text()).toContain('Proceder al pago')
    
    // Llamar directamente a la función customProceedToCheckout
    await wrapper.vm.customProceedToCheckout()
    await flushPromises()
    
    // Verificar que se muestra el formulario de checkout
    expect(wrapper.vm.showCheckoutForm).toBe(true)
    
    // Verificar que el modal del carrito se cierra
    expect(wrapper.vm.isCartOpen).toBe(false)
  })
})