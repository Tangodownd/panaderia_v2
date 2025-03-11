import { mount } from '@vue/test-utils';
import ContactForm from '../../../resources/js/components/ContactForm.vue';

describe('ContactForm.vue', () => {
  test('renders the form correctly', () => {
    const wrapper = mount(ContactForm);
    
    expect(wrapper.find('h2').text()).toBe('Contáctanos');
    expect(wrapper.find('form').exists()).toBe(true);
    expect(wrapper.findAll('input').length).toBe(3);
    expect(wrapper.find('textarea').exists()).toBe(true);
    expect(wrapper.find('button').text()).toBe('Enviar mensaje');
  });
  
  test('validates required fields', async () => {
    const wrapper = mount(ContactForm);
    
    await wrapper.find('form').trigger('submit');
    
    expect(wrapper.findAll('.error-message').length).toBe(4);
    expect(wrapper.find('.error-message').text()).toContain('obligatorio');
  });
  
  test('validates email format', async () => {
    const wrapper = mount(ContactForm);
    
    await wrapper.find('#name').setValue('Juan Pérez');
    await wrapper.find('#email').setValue('correo-invalido');
    await wrapper.find('#subject').setValue('Consulta');
    await wrapper.find('#message').setValue('Este es un mensaje de prueba');
    
    await wrapper.find('form').trigger('submit');
    
    expect(wrapper.findAll('.error-message').length).toBe(1);
    expect(wrapper.find('.error-message').text()).toContain('correo electrónico válido');
  });
  
  test('validates message length', async () => {
    const wrapper = mount(ContactForm);
    
    await wrapper.find('#name').setValue('Juan Pérez');
    await wrapper.find('#email').setValue('juan@example.com');
    await wrapper.find('#subject').setValue('Consulta');
    await wrapper.find('#message').setValue('Corto');
    
    await wrapper.find('form').trigger('submit');
    
    expect(wrapper.findAll('.error-message').length).toBe(1);
    expect(wrapper.find('.error-message').text()).toContain('al menos 10 caracteres');
  });
  
  test('clears error when field is updated', async () => {
    const wrapper = mount(ContactForm);
    
    await wrapper.find('form').trigger('submit');
    expect(wrapper.findAll('.error-message').length).toBe(4);
    
    await wrapper.find('#name').setValue('Juan Pérez');
    expect(wrapper.findAll('.error-message').length).toBe(3);
  });
  
  test('submits form with valid data and shows success message', async () => {
    const wrapper = mount(ContactForm);
    
    await wrapper.find('#name').setValue('Juan Pérez');
    await wrapper.find('#email').setValue('juan@example.com');
    await wrapper.find('#subject').setValue('Consulta sobre productos');
    await wrapper.find('#message').setValue('Me gustaría saber más sobre sus productos de panadería.');
    
    await wrapper.find('form').trigger('submit');
    
    // Esperar a que termine la simulación de envío
    await new Promise(resolve => setTimeout(resolve, 1600));
    
    expect(wrapper.find('form').exists()).toBe(false);
    expect(wrapper.find('.success-message').exists()).toBe(true);
    expect(wrapper.find('.success-message').text()).toContain('¡Gracias por tu mensaje!');
  });
  
  test('disables submit button while submitting', async () => {
    const wrapper = mount(ContactForm);
    
    await wrapper.find('#name').setValue('Juan Pérez');
    await wrapper.find('#email').setValue('juan@example.com');
    await wrapper.find('#subject').setValue('Consulta');
    await wrapper.find('#message').setValue('Este es un mensaje de prueba para verificar el comportamiento del botón.');
    
    await wrapper.find('form').trigger('submit');
    
    expect(wrapper.find('button').attributes('disabled')).toBeDefined();
    expect(wrapper.find('button').text()).toBe('Enviando...');
    
    // Esperar a que termine la simulación de envío
    await new Promise(resolve => setTimeout(resolve, 1600));
    
    expect(wrapper.find('.success-message').exists()).toBe(true);
  });
});