<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Editar UsuÃ¡rio</h2>
    </x-slot>

    <div class="py-6 max-w-xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="app-surface p-6">

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-lg text-sm">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('usuarios.update', $usuario) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
                    <input type="text" name="name" value="{{ old('name', $usuario->name) }}"
                           class="app-field w-full"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail *</label>
                    <input type="email" name="email" value="{{ old('email', $usuario->email) }}"
                           class="app-field w-full"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Perfil *</label>
                        <x-sgp-select
                            name="perfil"
                            :options="['atendente' => 'Atendente', 'admin' => 'Admin']"
                            :selected="$usuario->perfil"
                            placeholder="Selecione o perfil"
                            class="w-full"
                            required
                        />
                </div>

                <div class="border-t pt-4">
                    <p class="text-xs text-gray-400 mb-3">Deixe em branco para manter a senha atual</p>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nova Senha</label>
                            <input type="password" name="password"
                                   class="app-field w-full">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar Nova Senha</label>
                            <input type="password" name="password_confirmation"
                                   class="app-field w-full">
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit"
                            class="app-btn-primary">
                        Atualizar
                    </button>
                    <a href="{{ route('usuarios.index') }}"
                       class="app-btn-secondary">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

