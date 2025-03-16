import { mount, flushPromises } from '@vue/test-utils'
import CheckoutForm from '@/components/client/CheckoutForm.vue'
import axios from 'axios'

// Mock de axios
jest.mock('axios')

describe('CheckoutForm.vue', () => {
  let wrapper

  beforeEach(async () => {
    // Mock de respuesta de axios para cualquier solicitud
    axios.post.mockResolvedValue({ 
      data: { 
        success: true,
        order_id: '12345'
      } 
    })
    
    // Montar el componente con props y stubs necesarios
    wrapper = mount(CheckoutForm, {
      global: {
        stubs: {
          // Stub cualquier componente externo si es necesario
        },
        mocks: {
          // Proporcionar mocks para cualquier propiedad global
          $toast: {
            success: jest.fn(),
            error: jest.fn()
          }
        }
      },
      props: {
        // Proporcionar props necesarios
        cart: {
          id: 1,
          total: 100,
          items: [
            { id: 1, product_id: 1, quantity: 2, price: 50, product: { id: 1, name: 'Pan', price: 50 } }
          ]
        }
      },
      attachTo: document.body
    })
    
    // Esperar a que el componente se monte completamente
    await flushPromises()
    
    // Activar el modal de checkout manualmente
    wrapper.vm.openCheckoutModal()
    await flushPromises()
    
    // Imprimir el HTML después de abrir el modal
    console.log('HTML después de abrir el modal:', wrapper.html())
  })

  afterEach(() => {
    wrapper.unmount()
    jest.clearAllMocks()
  })

  test('muestra el formulario de checkout cuando se abre el modal', async () => {
    // Verificar que el modal está abierto
    expect(wrapper.vm.isCheckoutOpen).toBe(true)
    
    // Verificar que el formulario existe
    expect(wrapper.find('form').exists() || wrapper.find('.custom-modal-content').exists()).toBe(true)
    
    // Verificar que hay campos de entrada
    expect(wrapper.findAll('input').length).toBeGreaterThan(0)
    expect(wrapper.find('textarea').exists()).toBe(true)
  })

  test('valida el formulario antes de enviar', async () => {
    // Intentar enviar el formulario sin completar los campos
    await wrapper.find('button.btn-brown').trigger('click')
    await flushPromises()
    
    // Verificar que hay mensajes de error
    const errorMessages = wrapper.findAll('.text-danger')
    expect(errorMessages.length).toBeGreaterThan(0)
    
    // Verificar que no se llamó a axios.post
    expect(axios.post).not.toHaveBeenCalled()
  })

  test('envía el formulario correctamente cuando es válido', async () => {
    // Completar los campos del formulario
    await wrapper.find('#name').setValue('Usuario Test')
    await wrapper.find('#email').setValue('test@example.com')
    await wrapper.find('#shipping_address').setValue('Dirección de prueba')
    
    // Completar el teléfono
    const countryCodeSelect = wrapper.find('select')
    await countryCodeSelect.setValue('+58')
    await wrapper.find('#phone').setValue('4121234567')
    
    // Enviar el formulario
    await wrapper.find('button.btn-brown').trigger('click')
    await flushPromises()
    
    // Verificar que se llamó a axios.post
    expect(axios.post).toHaveBeenCalled()
    expect(axios.post).toHaveBeenCalledWith('/api/orders', expect.objectContaining({
      name: 'Usuario Test',
      email: 'test@example.com',
      phone: '+584121234567',
      shipping_address: 'Dirección de prueba'
    }), expect.any(Object))
  })

  test('valida correctamente el número de teléfono venezolano', async () => {
    // Seleccionar código de país de Venezuela
    const countryCodeSelect = wrapper.find('select')
    await countryCodeSelect.setValue('+58')
    
    // Probar con un número válido sin 0 inicial
    await wrapper.find('#phone').setValue('4141234567')
    
    // Validar manualmente
    wrapper.vm.validatePhone()
    await flushPromises()
    
    // Verificar que no hay error para este número
    expect(wrapper.vm.validationErrors.phone).toBe('')
    
    // Probar con un número válido con 0 inicial
    await wrapper.find('#phone').setValue('04141234567')
    
    // Validar manualmente
    wrapper.vm.validatePhone()
    await flushPromises()
    
    // Verificar que no hay error para este número
    expect(wrapper.vm.validationErrors.phone).toBe('')
  })
})