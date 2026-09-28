<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\LeadSource;
use App\Models\Offering;
use App\Models\Pipeline;
use App\Models\Stage;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['WhatsApp', 'Instagram', 'Facebook', 'Email'] as $name) {
            LeadSource::query()->updateOrCreate(['name' => $name]);
            Channel::query()->updateOrCreate(['name' => $name]);
        }

        $pipelines = [
            'neurobusiness-b2b' => [
                'name' => 'NeuroBusiness B2B',
                'default' => true,
                'stages' => [
                    ['lead', 'Lead', 10],
                    ['diagnostico-gratuito', 'Diagnóstico gratuito', 20],
                    ['calificado', 'Calificado', 35],
                    ['diagnostico-alto-impacto', 'Diagnóstico alto impacto', 50],
                    ['propuesta-sprint', 'Propuesta sprint', 65],
                    ['sprint-activo', 'Sprint activo', 80],
                    ['mentoring-retainer', 'Mentoring / retainer', 90],
                    ['ganado', 'Cerrado ganado', 100, true, false],
                    ['perdido', 'Cerrado perdido', 0, false, true],
                ],
            ],
            'programas-b2c' => [
                'name' => 'Programas B2C',
                'default' => false,
                'stages' => [
                    ['lead', 'Lead', 10],
                    ['contactado', 'Contactado', 25],
                    ['descubrimiento', 'Descubrimiento', 45],
                    ['inscripcion', 'Inscripción', 70],
                    ['activo', 'Activo', 90],
                    ['completado', 'Completado', 100, true, false],
                    ['perdido', 'Cerrado perdido', 0, false, true],
                ],
            ],
            'workshops' => [
                'name' => 'Workshops',
                'default' => false,
                'stages' => [
                    ['lead', 'Lead', 15],
                    ['brief', 'Brief', 35],
                    ['propuesta', 'Propuesta', 55],
                    ['confirmado', 'Confirmado', 80],
                    ['ejecutado', 'Ejecutado', 100, true, false],
                    ['perdido', 'Cerrado perdido', 0, false, true],
                ],
            ],
        ];

        $created = [];
        foreach ($pipelines as $slug => $config) {
            $pipeline = Pipeline::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $config['name'], 'is_default' => $config['default']]
            );
            foreach ($config['stages'] as $index => $stage) {
                Stage::query()->updateOrCreate(
                    ['pipeline_id' => $pipeline->id, 'slug' => $stage[0]],
                    [
                        'name' => $stage[1],
                        'position' => $index,
                        'probability_default' => $stage[2],
                        'is_won' => $stage[3] ?? false,
                        'is_lost' => $stage[4] ?? false,
                    ]
                );
            }
            $created[$slug] = $pipeline;
        }

        $offerings = [
            [
                'slug' => 'diagnostico-gratuito',
                'name' => 'Diagnóstico empresarial gratuito',
                'type' => 'b2b',
                'pipeline' => 'neurobusiness-b2b',
                'duration_days' => 1,
                'default_amount' => 0,
                'is_retainer' => false,
                'kpi_notes' => 'Semáforo de 5 dimensiones. No incluye plan ni KPIs personalizados.',
            ],
            [
                'slug' => 'diagnostico-alto-impacto',
                'name' => 'Diagnóstico estratégico de alto impacto',
                'type' => 'b2b',
                'pipeline' => 'neurobusiness-b2b',
                'duration_days' => 21,
                'default_amount' => 2500,
                'is_retainer' => false,
                'kpi_notes' => '3 KPIs a 90 días, plan de 30 días, mapa de fricción.',
            ],
            [
                'slug' => 'sprint-implementacion',
                'name' => 'Sprint de implementación',
                'type' => 'b2b',
                'pipeline' => 'neurobusiness-b2b',
                'duration_days' => 84,
                'default_amount' => 8000,
                'is_retainer' => false,
                'kpi_notes' => 'Tracks liderazgo, procesos o ejecución. 8–12 semanas.',
            ],
            [
                'slug' => 'mentoring-mensual',
                'name' => 'Mentoring y acompañamiento mensual',
                'type' => 'b2b',
                'pipeline' => 'neurobusiness-b2b',
                'duration_days' => 30,
                'default_amount' => 1200,
                'is_retainer' => true,
                'retainer_months' => 6,
                'kpi_notes' => 'Seguimiento mensual de KPIs + coaching directivo. 6–12 meses.',
            ],
            [
                'slug' => 'certificacion-personal',
                'name' => 'Certificación / programa personal',
                'type' => 'b2c',
                'pipeline' => 'programas-b2c',
                'duration_days' => 60,
                'default_amount' => 900,
                'is_retainer' => false,
                'kpi_notes' => 'Acompañamiento estructurado de transformación personal.',
            ],
            [
                'slug' => 'workshop-in-company',
                'name' => 'Workshop in-company',
                'type' => 'workshop',
                'pipeline' => 'workshops',
                'duration_days' => 2,
                'default_amount' => 1800,
                'is_retainer' => false,
                'kpi_notes' => 'Liderazgo, equipo, comunicación, ventas o indoor/outdoor.',
            ],
        ];

        foreach ($offerings as $offering) {
            Offering::query()->updateOrCreate(
                ['slug' => $offering['slug']],
                [
                    'name' => $offering['name'],
                    'type' => $offering['type'],
                    'pipeline_id' => $created[$offering['pipeline']]->id,
                    'duration_days' => $offering['duration_days'],
                    'default_amount' => $offering['default_amount'],
                    'is_retainer' => $offering['is_retainer'],
                    'retainer_months' => $offering['retainer_months'] ?? null,
                    'kpi_notes' => $offering['kpi_notes'],
                    'description' => $offering['kpi_notes'],
                ]
            );
        }
    }
}
