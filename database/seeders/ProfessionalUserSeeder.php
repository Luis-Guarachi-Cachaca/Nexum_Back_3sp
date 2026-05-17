<?php

namespace Database\Seeders;

use App\Models\Portfolio;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProfessionalUserSeeder extends Seeder
{
    public function run(): void
    {
        $professionals = [
            [
                'user' => [
                    'first_name'        => 'Ana',
                    'last_name'         => 'García',
                    'email'             => 'ana.garcia@portfolio.test',
                    'password'          => Hash::make('Password123!'),
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ],
                'portfolio' => [
                    'profession'   => 'Full Stack Developer',
                    'biography'    => 'Desarrolladora web con 5 años de experiencia en Laravel y React. Apasionada por el código limpio y las buenas prácticas.',
                    'linkedin_url' => 'https://www.linkedin.com/in/ana-garcia-dev',
                    'github_url'   => 'https://github.com/anagarcia-dev',
                    'global_privacy' => 'public',
                    'show_projects' => true,
                    'show_skills' => true,
                    'show_experience' => true,
                    'show_certifications' => true,
                ],
            ],
            [
                'user' => [
                    'first_name'        => 'Carlos',
                    'last_name'         => 'Méndez',
                    'email'             => 'carlos.mendez@portfolio.test',
                    'password'          => Hash::make('Password123!'),
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ],
                'portfolio' => [
                    'profession'   => 'UX/UI Designer',
                    'biography'    => 'Diseñador de experiencias digitales con enfoque en accesibilidad e interfaces intuitivas. 7 años de experiencia en productos SaaS.',
                    'linkedin_url' => 'https://www.linkedin.com/in/carlos-mendez-ux',
                    'github_url'   => null,
                    'global_privacy' => 'public',
                    'show_projects' => true,
                    'show_skills' => true,
                    'show_experience' => true,
                    'show_certifications' => true,
                ],
            ],
            [
                'user' => [
                    'first_name'        => 'Sofía',
                    'last_name'         => 'Romero',
                    'email'             => 'sofia.romero@portfolio.test',
                    'password'          => Hash::make('Password123!'),
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ],
                'portfolio' => [
                    'profession'   => 'Data Scientist',
                    'biography'    => 'Científica de datos especializada en machine learning y visualización. Experiencia en Python, TensorFlow y análisis predictivo.',
                    'linkedin_url' => 'https://www.linkedin.com/in/sofia-romero-data',
                    'github_url'   => 'https://github.com/sofiaromero-ds',
                    'global_privacy' => 'public',
                    'show_projects' => true,
                    'show_skills' => true,
                    'show_experience' => true,
                    'show_certifications' => true,
                ],
            ],
        ];

        foreach ($professionals as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['user']['email']],
                $data['user']
            );

            $user->syncRoles(['professional']);

            Portfolio::firstOrCreate(
                ['user_id' => $user->id],
                array_merge($data['portfolio'], ['user_id' => $user->id])
            );
        }
    }
}