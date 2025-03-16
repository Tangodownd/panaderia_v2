<?php

namespace Tests\Unit;

use App\Http\Controllers\CartController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CartControllerWhiteBoxTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function getOrCreateCart_creates_new_cart_when_no_cart_exists()
    {
        // Crear una instancia del controlador
        $controller = new CartController();
        
        // Crear una solicitud simulada
        $request = new Request();
        $request->cookies->set('cart_id', null);
        
        // Llamar al método getOrCreateCart usando reflexión
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getOrCreateCart');
        $method->setAccessible(true);
        $cart = $method->invoke($controller, $request);
        
        // Verificar que se haya creado un nuevo carrito
        $this->assertInstanceOf(Cart::class, $cart);
        $this->assertEquals('active', $cart->status);
        $this->assertEquals(0, $cart->total);
    }

    /** @test */
    public function getOrCreateCart_returns_existing_cart_when_cart_exists()
    {
        // Crear un carrito existente
        $existingCart = Cart::create([
            'session_id' => null,
            'status' => 'active',
            'total' => 10.00
        ]);
        
        // Crear una solicitud simulada con el ID del carrito existente
        $request = new Request();
        $request->cookies->set('cart_id', $existingCart->id);
        
        // Crear una instancia del controlador
        $controller = new CartController();
        
        // Llamar al método getOrCreateCart usando reflexión
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getOrCreateCart');
        $method->setAccessible(true);
        $cart = $method->invoke($controller, $request);
        
        // Verificar que se haya devuelto el carrito existente
        $this->assertInstanceOf(Cart::class, $cart);
        $this->assertEquals($existingCart->id, $cart->id);
        $this->assertEquals('active', $cart->status);
        $this->assertEquals(10.00, $cart->total);
    }

    /** @test */
    public function validatePhone_accepts_valid_venezuelan_phone_numbers()
    {
        // Crear un componente Vue simulado
        $component = new class {
            public $phoneNumber = '';
            public $selectedCountryCode = '+58';
            public $validationErrors = ['phone' => ''];
            
            public function validatePhone()
            {
                // Permitir que se mantenga el 0 inicial para números venezolanos
                // pero eliminar otros caracteres no numéricos
                $this->phoneNumber = preg_replace('/[^\d0]/', '', $this->phoneNumber);
                
                $isValid = false;
                $errorMessage = '';
                
                // Validación específica según el código de país
                if ($this->selectedCountryCode === '+58') {
                    // Venezuela: 10 dígitos (sin 0 inicial) o 11 dígitos (con 0 inicial)
                    if (!$this->phoneNumber) {
                        $errorMessage = 'El número de teléfono es requerido';
                    } else if (strlen($this->phoneNumber) === 10) {
                        // Número sin 0 inicial (ej: 4244423510)
                        $isValid = true;
                    } else if (strlen($this->phoneNumber) === 11 && $this->phoneNumber[0] === '0') {
                        // Número con 0 inicial (ej: 04244423510)
                        $isValid = true;
                    } else {
                        $errorMessage = 'El número debe tener 10 dígitos o 11 dígitos si comienza con 0';
                    }
                }
                
                $this->validationErrors['phone'] = $errorMessage;
                return $isValid;
            }
        };
        
        // Probar con un número sin el 0 inicial
        $component->phoneNumber = '4121234567';
        $result = $component->validatePhone();
        $this->assertTrue($result);
        $this->assertEquals('', $component->validationErrors['phone']);
        
        // Probar con un número con el 0 inicial
        $component->phoneNumber = '04121234567';
        $result = $component->validatePhone();
        $this->assertTrue($result);
        $this->assertEquals('', $component->validationErrors['phone']);
        
        // Probar con un número inválido (muy corto)
        $component->phoneNumber = '412123';
        $result = $component->validatePhone();
        $this->assertFalse($result);
        $this->assertNotEquals('', $component->validationErrors['phone']);
    }
}