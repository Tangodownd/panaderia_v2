<template>
    <div class="contact-form-container">
      <div v-if="submitted" class="success-message">
        <h3>¡Gracias por tu mensaje!</h3>
        <p>Hemos recibido tu mensaje y te responderemos pronto.</p>
        <button @click="resetForm" class="btn btn-primary">Enviar otro mensaje</button>
      </div>
      <form v-else @submit.prevent="submitForm" class="contact-form">
        <h2>Contáctanos</h2>
        
        <div class="form-group">
          <label for="name">Nombre</label>
          <input
            type="text"
            id="name"
            v-model="form.name"
            class="form-control"
            :class="{ 'is-invalid': errors.name }"
            @input="clearError('name')"
          />
          <div v-if="errors.name" class="error-message">{{ errors.name }}</div>
        </div>
        
        <div class="form-group">
          <label for="email">Correo electrónico</label>
          <input
            type="email"
            id="email"
            v-model="form.email"
            class="form-control"
            :class="{ 'is-invalid': errors.email }"
            @input="clearError('email')"
          />
          <div v-if="errors.email" class="error-message">{{ errors.email }}</div>
        </div>
        
        <div class="form-group">
          <label for="subject">Asunto</label>
          <input
            type="text"
            id="subject"
            v-model="form.subject"
            class="form-control"
            :class="{ 'is-invalid': errors.subject }"
            @input="clearError('subject')"
          />
          <div v-if="errors.subject" class="error-message">{{ errors.subject }}</div>
        </div>
        
        <div class="form-group">
          <label for="message">Mensaje</label>
          <textarea
            id="message"
            v-model="form.message"
            class="form-control"
            :class="{ 'is-invalid': errors.message }"
            rows="5"
            @input="clearError('message')"
          ></textarea>
          <div v-if="errors.message" class="error-message">{{ errors.message }}</div>
        </div>
        
        <div class="form-group">
          <button type="submit" class="btn btn-primary" :disabled="isSubmitting">
            {{ isSubmitting ? 'Enviando...' : 'Enviar mensaje' }}
          </button>
        </div>
      </form>
    </div>
  </template>
  
  <script>
  export default {
    name: 'ContactForm',
    data() {
      return {
        form: {
          name: '',
          email: '',
          subject: '',
          message: ''
        },
        errors: {},
        isSubmitting: false,
        submitted: false
      };
    },
    methods: {
      validateForm() {
        this.errors = {};
        let isValid = true;
        
        if (!this.form.name.trim()) {
          this.errors.name = 'El nombre es obligatorio';
          isValid = false;
        }
        
        if (!this.form.email.trim()) {
          this.errors.email = 'El correo electrónico es obligatorio';
          isValid = false;
        } else if (!this.isValidEmail(this.form.email)) {
          this.errors.email = 'Por favor, introduce un correo electrónico válido';
          isValid = false;
        }
        
        if (!this.form.subject.trim()) {
          this.errors.subject = 'El asunto es obligatorio';
          isValid = false;
        }
        
        if (!this.form.message.trim()) {
          this.errors.message = 'El mensaje es obligatorio';
          isValid = false;
        } else if (this.form.message.trim().length < 10) {
          this.errors.message = 'El mensaje debe tener al menos 10 caracteres';
          isValid = false;
        }
        
        return isValid;
      },
      
      clearError(field) {
        if (this.errors[field]) {
          delete this.errors[field];
        }
      },
      
      isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
      },
      
      async submitForm() {
        if (!this.validateForm()) {
          return;
        }
        
        this.isSubmitting = true;
        
        try {
          // Simulación de envío a API
          await new Promise(resolve => setTimeout(resolve, 1000));
          
          // En un caso real, aquí harías una llamada a la API
          // const response = await axios.post('/api/contact', this.form);
          
          this.submitted = true;
          this.isSubmitting = false;
        } catch (error) {
          console.error('Error al enviar el formulario:', error);
          this.isSubmitting = false;
          alert('Ha ocurrido un error al enviar el formulario. Por favor, inténtalo de nuevo más tarde.');
        }
      },
      
      resetForm() {
        this.form = {
          name: '',
          email: '',
          subject: '',
          message: ''
        };
        this.errors = {};
        this.submitted = false;
      }
    }
  };
  </script>
  
  <style scoped>
  .contact-form-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
  }
  
  .contact-form h2 {
    margin-bottom: 20px;
    color: #333;
  }
  
  .form-group {
    margin-bottom: 20px;
  }
  
  .form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
  }
  
  .form-control:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
  }
  
  .is-invalid {
    border-color: #dc3545;
  }
  
  .error-message {
    color: #dc3545;
    font-size: 14px;
    margin-top: 5px;
  }
  
  .btn {
    display: inline-block;
    font-weight: 400;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    user-select: none;
    border: 1px solid transparent;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    border-radius: 0.25rem;
    transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
  }
  
  .btn-primary {
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
  }
  
  .btn-primary:hover {
    color: #fff;
    background-color: #0069d9;
    border-color: #0062cc;
  }
  
  .btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
  }
  
  .success-message {
    text-align: center;
    padding: 20px;
    background-color: #d4edda;
    border-radius: 4px;
    color: #155724;
  }
  
  .success-message h3 {
    margin-bottom: 10px;
  }
  
  .success-message p {
    margin-bottom: 20px;
  }
  </style>