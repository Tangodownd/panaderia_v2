<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $now = Carbon::now();
        
        $products = [
            // Categoría 1: Panes
            [
                'name' => 'Pan Campesino',
                'description' => 'Tradición venezolana en cada bocado. Masa suave rellena de sabor artesanal.',
                'price' => 6.00,
                'stock' => 20,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Pan Dulce',
                'description' => 'Esponjoso y con un toque de dulzura, perfecto para el desayuno.',
                'price' => 1.50,
                'stock' => 15,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Pan de Queso',
                'description' => 'Una delicia salada con queso fundido en cada mordisco.',
                'price' => 2.00,
                'stock' => 15,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Pan Francés',
                'description' => 'Corteza crujiente y miga suave, ideal para acompañar comidas.',
                'price' => 2.50,
                'stock' => 25,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Pan de Jamón',
                'description' => 'Tradicional pan navideño relleno de jamón, aceitunas y pasas.',
                'price' => 12.00,
                'stock' => 10,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Cachito',
                'description' => 'Delicioso pan en forma de media luna relleno de jamón.',
                'price' => 3.50,
                'stock' => 20,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Pan Canilla',
                'description' => 'El clásico pan venezolano, perfecto para cualquier comida.',
                'price' => 1.00,
                'stock' => 30,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Pan Integral',
                'description' => 'Opción saludable elaborada con harinas integrales.',
                'price' => 3.50,
                'stock' => 15,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Pan de Guayaba',
                'description' => 'Delicioso pan dulce con relleno de guayaba.',
                'price' => 2.50,
                'stock' => 12,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Pan de Coco',
                'description' => 'Suave pan dulce con sabor a coco, típico de la gastronomía venezolana.',
                'price' => 2.50,
                'stock' => 15,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Pan de Leche',
                'description' => 'Suave y esponjoso, con un delicado sabor a leche.',
                'price' => 2.00,
                'stock' => 20,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Pan de Maíz',
                'description' => 'Tradicional pan venezolano elaborado con harina de maíz.',
                'price' => 2.50,
                'stock' => 15,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Acema',
                'description' => 'Pan tradicional andino, de textura densa y sabor único.',
                'price' => 3.00,
                'stock' => 10,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Pan de Ajonjolí',
                'description' => 'Pan cubierto con semillas de ajonjolí, ideal para sándwiches.',
                'price' => 2.50,
                'stock' => 15,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Pan Sobado',
                'description' => 'Pan de textura suave y elástica, perfecto para el desayuno.',
                'price' => 2.00,
                'stock' => 20,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Croissant',
                'description' => 'Hojaldre en forma de media luna, mantequilloso y crujiente.',
                'price' => 3.50,
                'stock' => 15,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Palmeritas',
                'description' => 'Dulces hojaldres en forma de corazón, caramelizados.',
                'price' => 2.00,
                'stock' => 20,
                'image' => null,
                'category_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            
            // Categoría 2: Pastelería/Repostería
            [
                'name' => 'Milhoja',
                'description' => 'Capas crujientes de hojaldre rellenas de crema pastelera.',
                'price' => 1.50,
                'stock' => 15,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Tartaleta de Fresa',
                'description' => 'Base de masa quebrada con crema dulce y fresas frescas.',
                'price' => 1.50,
                'stock' => 20,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Golfeado',
                'description' => 'Suave espiral de masa dulce, rellena de papelón, anís y queso.',
                'price' => 3.00,
                'stock' => 15,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Quesillo',
                'description' => 'Tradicional postre venezolano similar al flan, con caramelo.',
                'price' => 8.00,
                'stock' => 10,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Torta Tres Leches',
                'description' => 'Bizcocho empapado en tres tipos de leche, cubierto con merengue.',
                'price' => 15.00,
                'stock' => 8,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Brazo Gitano',
                'description' => 'Bizcocho enrollado relleno de crema o mermelada.',
                'price' => 10.00,
                'stock' => 10,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Polvorosa',
                'description' => 'Galleta tradicional de textura arenosa y sabor a mantequilla.',
                'price' => 1.00,
                'stock' => 25,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Besito de Coco',
                'description' => 'Pequeños dulces de coco rallado, suaves y aromáticos.',
                'price' => 0.75,
                'stock' => 30,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Torta de Chocolate',
                'description' => 'Esponjosa torta de chocolate con cobertura de ganache.',
                'price' => 18.00,
                'stock' => 8,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Cachapa de Anís',
                'description' => 'Dulce tradicional a base de harina y anís.',
                'price' => 1.50,
                'stock' => 20,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Churros',
                'description' => 'Masa frita crujiente, espolvoreada con azúcar y canela.',
                'price' => 2.50,
                'stock' => 20,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Marquesa de Chocolate',
                'description' => 'Postre frío de galletas y crema de chocolate.',
                'price' => 12.00,
                'stock' => 10,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Bienmesabe',
                'description' => 'Dulce tradicional a base de coco, huevo y azúcar.',
                'price' => 10.00,
                'stock' => 12,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Dulce de Lechosa',
                'description' => 'Tradicional postre venezolano de lechosa (papaya) en almíbar.',
                'price' => 8.00,
                'stock' => 15,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Conserva de Coco',
                'description' => 'Dulce tradicional de coco rallado con azúcar y especias.',
                'price' => 5.00,
                'stock' => 15,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Catalina',
                'description' => 'Galleta tradicional venezolana con sabor a anís.',
                'price' => 0.80,
                'stock' => 30,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Suspiro',
                'description' => 'Dulce merengue que se deshace en la boca.',
                'price' => 1.00,
                'stock' => 25,
                'image' => null,
                'category_id' => 2,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            
            // Categoría 3: Charcutería/Quesos
            [
                'name' => 'Queso Llanero',
                'description' => 'Fresco y salado, ideal para acompañar tus arepas.',
                'price' => 4.50,
                'stock' => 15,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Mortadela',
                'description' => 'Embutido clásico con un toque de especias, perfecto para sándwiches.',
                'price' => 6.00,
                'stock' => 20,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Queso Amarillo',
                'description' => 'Cremoso y delicioso, ideal para derretir y agregar a tus comidas.',
                'price' => 10.00,
                'stock' => 15,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Jamón Cocido',
                'description' => 'Suave y jugoso, perfecto para sándwiches y cachitos.',
                'price' => 8.00,
                'stock' => 15,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Queso de Mano',
                'description' => 'Tradicional queso venezolano, suave y flexible.',
                'price' => 7.50,
                'stock' => 12,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Queso Telita',
                'description' => 'Queso suave y fresco, típico del oriente venezolano.',
                'price' => 7.00,
                'stock' => 15,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Queso Guayanés',
                'description' => 'Queso semi-duro con sabor suave, ideal para arepas.',
                'price' => 8.50,
                'stock' => 10,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Salchichón',
                'description' => 'Embutido curado con especias, perfecto para picar.',
                'price' => 7.00,
                'stock' => 15,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Queso Palmita',
                'description' => 'Queso fresco y suave, típico de los Andes venezolanos.',
                'price' => 9.00,
                'stock' => 10,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Jamón Serrano',
                'description' => 'Jamón curado de alta calidad, con sabor intenso.',
                'price' => 12.00,
                'stock' => 8,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Chorizo',
                'description' => 'Embutido sazonado con pimentón y especias.',
                'price' => 7.50,
                'stock' => 15,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Queso Paisa',
                'description' => 'Queso fresco con textura firme, ideal para arepas y cachapas.',
                'price' => 8.00,
                'stock' => 12,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Salchichas',
                'description' => 'Embutido suave y jugoso, perfecto para perros calientes.',
                'price' => 5.00,
                'stock' => 20,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Queso Mozarella',
                'description' => 'Queso suave ideal para gratinar y derretir.',
                'price' => 9.50,
                'stock' => 15,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
            [
                'name' => 'Tocineta',
                'description' => 'Deliciosas tiras de tocino ahumado.',
                'price' => 8.50,
                'stock' => 15,
                'image' => null,
                'category_id' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'discount' => 0.00
            ],
        ];
        
        // Insertar los productos en la base de datos
        DB::table('products')->insert($products);
        
        $this->command->info('Productos insertados correctamente. Ahora puedes ejecutar el comando para descargar las imágenes.');
    }
}