<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('owner:inject {--email=owner@kopikita.test : Email akun owner} {--password=123123 : Password akun owner} {--name=Owner KopiKita : Nama akun owner}')]
#[Description('Inject atau perbarui akun owner dengan password dan role owner')]
class CreateOwnerCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->option('email');
        $password = (string) $this->option('password');
        $name = (string) $this->option('name');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => 'owner',
            ]
        );

        $this->info('✓ Akun Owner berhasil di-inject ke database!');
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $user->name],
                ['Email', $user->email],
                ['Password', $password],
                ['Role', $user->role],
                ['Status', $user->wasRecentlyCreated ? 'Baru dibuat' : 'Berhasil diperbarui'],
            ]
        );

        return self::SUCCESS;
    }
}
