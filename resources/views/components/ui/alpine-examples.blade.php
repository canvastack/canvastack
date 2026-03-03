{{--
    Alpine.js Component Examples
    
    This file demonstrates various Alpine.js patterns and components
    used throughout CanvaStack.
--}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alpine.js Components - CanvaStack</title>
    @vite(['resources/css/canvastack.css', 'resources/js/canvastack.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100">
    <div class="container mx-auto px-4 py-12 max-w-7xl">
        <div class="mb-12">
            <h1 class="text-4xl font-bold mb-4 gradient-text">Alpine.js Components</h1>
            <p class="text-gray-600 dark:text-gray-400">Interactive component examples for CanvaStack</p>
        </div>

        <div class="grid gap-8">
            {{-- Dark Mode Toggle Examples --}}
            <x-ui.card>
                <h2 class="text-2xl font-bold mb-6">Dark Mode Toggle</h2>
                
                <div class="space-y-6">
                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Icon Button (Default)</h3>
                        <x-ui.dark-mode-toggle />
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Full Button</h3>
                        <x-ui.dark-mode-toggle variant="button" />
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Toggle Switch</h3>
                        <x-ui.dark-mode-toggle variant="switch" />
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Different Sizes</h3>
                        <div class="flex items-center gap-4">
                            <x-ui.dark-mode-toggle size="sm" />
                            <x-ui.dark-mode-toggle size="md" />
                            <x-ui.dark-mode-toggle size="lg" />
                        </div>
                    </div>
                </div>
            </x-ui.card>

            {{-- Dropdown Examples --}}
            <x-ui.card>
                <h2 class="text-2xl font-bold mb-6">Dropdown Component</h2>
                
                <div class="space-y-6">
                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Basic Dropdown</h3>
                        <x-ui.dropdown>
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                                    Options
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </button>
                            </x-slot>

                            <x-ui.dropdown-link href="#">Profile</x-ui.dropdown-link>
                            <x-ui.dropdown-link href="#">Settings</x-ui.dropdown-link>
                            <x-ui.dropdown-link href="#">Logout</x-ui.dropdown-link>
                        </x-ui.dropdown>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Left Aligned</h3>
                        <x-ui.dropdown align="left">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-2 px-4 py-2 gradient-bg text-white rounded-lg">
                                    Actions
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </button>
                            </x-slot>

                            <x-ui.dropdown-link href="#">Edit</x-ui.dropdown-link>
                            <x-ui.dropdown-link href="#">Duplicate</x-ui.dropdown-link>
                            <x-ui.dropdown-link href="#">Delete</x-ui.dropdown-link>
                        </x-ui.dropdown>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Wide Dropdown</h3>
                        <x-ui.dropdown width="72">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg">
                                    More Options
                                    <i data-lucide="chevron-down" class="w-4 h-4"></i>
                                </button>
                            </x-slot>

                            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800">
                                <p class="text-sm font-semibold">Account Settings</p>
                                <p class="text-xs text-gray-500">Manage your account</p>
                            </div>
                            <x-ui.dropdown-link href="#">Profile Settings</x-ui.dropdown-link>
                            <x-ui.dropdown-link href="#">Privacy Settings</x-ui.dropdown-link>
                            <x-ui.dropdown-link href="#">Notification Settings</x-ui.dropdown-link>
                        </x-ui.dropdown>
                    </div>
                </div>
            </x-ui.card>

            {{-- Modal Examples --}}
            <x-ui.card>
                <h2 class="text-2xl font-bold mb-6">Modal Component</h2>
                
                <div class="space-y-4">
                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Basic Modal</h3>
                        <x-ui.button @click="$dispatch('open-modal', 'basic-modal')">
                            Open Basic Modal
                        </x-ui.button>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Confirmation Modal</h3>
                        <x-ui.button variant="danger" @click="$dispatch('open-modal', 'confirm-modal')">
                            Delete Item
                        </x-ui.button>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Large Modal</h3>
                        <x-ui.button @click="$dispatch('open-modal', 'large-modal')">
                            Open Large Modal
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.card>

            {{-- Custom Alpine.js Examples --}}
            <x-ui.card>
                <h2 class="text-2xl font-bold mb-6">Custom Alpine.js Patterns</h2>
                
                <div class="space-y-8">
                    {{-- Counter --}}
                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Counter</h3>
                        <div x-data="{ count: 0 }" class="flex items-center gap-4">
                            <button @click="count--" class="px-4 py-2 bg-gray-200 dark:bg-gray-800 rounded-lg">-</button>
                            <span class="text-2xl font-bold" x-text="count"></span>
                            <button @click="count++" class="px-4 py-2 bg-gray-200 dark:bg-gray-800 rounded-lg">+</button>
                        </div>
                    </div>

                    {{-- Tabs --}}
                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Tabs</h3>
                        <div x-data="{ tab: 'tab1' }">
                            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-800 mb-4">
                                <button 
                                    @click="tab = 'tab1'"
                                    :class="tab === 'tab1' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent'"
                                    class="px-4 py-2 border-b-2 font-medium text-sm"
                                >
                                    Tab 1
                                </button>
                                <button 
                                    @click="tab = 'tab2'"
                                    :class="tab === 'tab2' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent'"
                                    class="px-4 py-2 border-b-2 font-medium text-sm"
                                >
                                    Tab 2
                                </button>
                                <button 
                                    @click="tab = 'tab3'"
                                    :class="tab === 'tab3' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent'"
                                    class="px-4 py-2 border-b-2 font-medium text-sm"
                                >
                                    Tab 3
                                </button>
                            </div>
                            <div x-show="tab === 'tab1'" class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                                Content for Tab 1
                            </div>
                            <div x-show="tab === 'tab2'" class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg" style="display: none;">
                                Content for Tab 2
                            </div>
                            <div x-show="tab === 'tab3'" class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg" style="display: none;">
                                Content for Tab 3
                            </div>
                        </div>
                    </div>

                    {{-- Accordion --}}
                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Accordion</h3>
                        <div class="space-y-2">
                            <div x-data="{ open: false }" class="border border-gray-200 dark:border-gray-800 rounded-lg">
                                <button 
                                    @click="open = !open"
                                    class="w-full flex items-center justify-between p-4 text-left"
                                >
                                    <span class="font-medium">Section 1</span>
                                    <i data-lucide="chevron-down" class="w-4 h-4" :class="{ 'rotate-180': open }"></i>
                                </button>
                                <div x-show="open" x-collapse class="px-4 pb-4">
                                    Content for section 1
                                </div>
                            </div>
                            <div x-data="{ open: false }" class="border border-gray-200 dark:border-gray-800 rounded-lg">
                                <button 
                                    @click="open = !open"
                                    class="w-full flex items-center justify-between p-4 text-left"
                                >
                                    <span class="font-medium">Section 2</span>
                                    <i data-lucide="chevron-down" class="w-4 h-4" :class="{ 'rotate-180': open }"></i>
                                </button>
                                <div x-show="open" x-collapse class="px-4 pb-4">
                                    Content for section 2
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Toast Notification --}}
                    <div>
                        <h3 class="text-sm font-semibold mb-3 text-gray-600 dark:text-gray-400">Toast Notification</h3>
                        <div x-data="{ show: false, message: '' }">
                            <button 
                                @click="show = true; message = 'Success! Your changes have been saved.'; setTimeout(() => show = false, 3000)"
                                class="px-4 py-2 gradient-bg text-white rounded-lg"
                            >
                                Show Toast
                            </button>
                            
                            <div 
                                x-show="show"
                                x-transition
                                class="fixed bottom-4 right-4 bg-emerald-500 text-white px-6 py-3 rounded-lg shadow-lg"
                                style="display: none;"
                            >
                                <span x-text="message"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    {{-- Modal Definitions --}}
    <x-ui.modal name="basic-modal">
        <x-slot name="header">
            <h3 class="text-lg font-bold">Basic Modal</h3>
        </x-slot>

        <p class="text-sm text-gray-600 dark:text-gray-400">
            This is a basic modal example with header and footer.
        </p>

        <x-slot name="footer">
            <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'basic-modal')">
                Close
            </x-ui.button>
            <x-ui.button variant="primary">
                Save Changes
            </x-ui.button>
        </x-slot>
    </x-ui.modal>

    <x-ui.modal name="confirm-modal" max-width="sm">
        <x-slot name="header">
            <h3 class="text-lg font-bold text-red-600 dark:text-red-400">Confirm Delete</h3>
        </x-slot>

        <p class="text-sm text-gray-600 dark:text-gray-400">
            Are you sure you want to delete this item? This action cannot be undone.
        </p>

        <x-slot name="footer">
            <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'confirm-modal')">
                Cancel
            </x-ui.button>
            <x-ui.button variant="danger">
                Delete
            </x-ui.button>
        </x-slot>
    </x-ui.modal>

    <x-ui.modal name="large-modal" max-width="2xl">
        <x-slot name="header">
            <h3 class="text-lg font-bold">Large Modal</h3>
        </x-slot>

        <div class="space-y-4">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                This is a large modal with more content.
            </p>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <h4 class="font-semibold mb-2">Column 1</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Content here</p>
                </div>
                <div class="p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                    <h4 class="font-semibold mb-2">Column 2</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Content here</p>
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <x-ui.button variant="secondary" @click="$dispatch('close-modal', 'large-modal')">
                Close
            </x-ui.button>
        </x-slot>
    </x-ui.modal>
</body>
</html>
