<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm,router } from '@inertiajs/vue3';
import { ref } from 'vue';

const campaignName = ref(null);
const uploadCsv = ref(null);
const successMessage = ref('');
const errorMessage = ref('');
const errors = ref([]);

const form = useForm({
    name: '',
    upload_csv: null,
});

const props = defineProps({
    campaign: Object,
});

const goToCampaign = (id) => {
    router.visit('/campaign/send/'+id)
};

const uploadCampaigns = () => {
    form.post(route('campaign.store'), {
        preserveScroll: true,
        onSuccess: (response) => {
            form.reset();
            successMessage.value = "Campaign created successfully";
            errorMessage.value = ''; // Clear previous error message
            errors.value = []; // Clear previous errors
        },
        onError: (response) => {
            if (response.status === 422) {
                // Handle validation errors
                errorMessage.value = response.props.message || 'Validation failed.';
                errors.value = response.props.errors || [];
            } else {
                // Handle other errors
                errorMessage.value = 'An error occurred. Please try again.';
                errors.value = [];
            }

            // Focus on the input field with the error
            if (form.errors.name) {
                form.reset('name');
                campaignName.value?.focus();
            }
            if (form.errors.upload_csv) {
                form.reset('upload_csv');
                uploadCsv.value?.focus();
            }
            if (form.errors.email) {
                form.reset('upload_csv');
                errorMessage.value = form.errors.email;
                errors.value = [];
            }
        },
    });
};
</script>

<template>
    <Head title="Campaigns" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Campaigns</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4 my-5">
                    <form @submit.prevent="uploadCampaigns">
                        <div>
                            <h3 class="block font-medium leading-6 text-gray-900 mb-2">Create Campaign</h3>
                            <div class="flex items-center space-x-4">
                                <!-- Campaign Name Input -->
                                <div class="w-full">
                                    <input
                                        type="text"
                                        v-model="form.name"
                                        ref="campaignName"
                                        name="name"
                                        id="name"
                                        class="block w-full rounded-md border-0 py-1.5 pl-7 pr-20 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                        placeholder="Campaign Name"
                                    >
                                    <!-- Error Message for Campaign Name -->
                                    <p v-if="form.errors.name" class="text-red-500 text-sm">{{ form.errors.name }}</p>
                                </div>

                                <!-- Upload CSV Input -->
                                <div class="w-full">
                                    <input
                                        type="file"
                                        ref="uploadCsv"
                                        name="upload_csv"
                                        id="upload_csv"
                                        @change="e => form.upload_csv = e.target.files[0]"
                                        class="block w-full rounded-md border-0 py-1.5 pl-7 pr-20 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                                    >
                                    <!-- Error Message for Upload CSV -->
                                    <p v-if="form.errors.upload_csv" class="text-red-500 text-sm">{{ form.errors.upload_csv }}</p>
                                </div>

                                <!-- Upload Button -->
                                <button
                                    class="bg-blue-500 text-white py-2 px-5 shadow-lg rounded-md hover:bg-blue-600"
                                    :disabled="form.processing"
                                >
                                    Upload
                                </button>
                            </div>
                            <p v-if="successMessage" class="text-green-500 text-sm mt-2">{{ successMessage }}</p>
                            <p v-if="errorMessage" class="text-red-500 text-sm mt-2">{{ errorMessage }}</p>
                            <div v-if="errors.length">
                                <p class="text-red-500 text-sm mt-2">Some rows contain invalid data:</p>
                                <ul>
                                    <li v-for="(error, index) in errors" :key="index">
                                        <p>Row {{ index + 1 }}: {{ error.errors }}</p>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-200">
                        <li
                            class="p-4 flex justify-between items-center cursor-pointer hover:bg-gray-50"
                            v-for="item in props.campaign.data"
                            :key="item.id"
                        >
                            <div class="w-full sm:w-1/2 lg:w-2/5 xl:w-2/5">
                                <span class="text-blue-600 hover:underline">{{ item.name }}</span>
                            </div>
                            <div class="w-full sm:w-1/2 lg:w-2/5 xl:w-2/5 text-right">
                                Total Contact:-{{ item.get_contact_count }} ||
                                <button class="bg-orange-500 text-white  px-2 shadow-lg rounded-md hover:bg-orange-600" @click="goToCampaign(item.id)"> Send Campaign </button>
                            </div>
                        </li>
                    </ul>


                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Optional: Add additional styles if needed */
</style>
