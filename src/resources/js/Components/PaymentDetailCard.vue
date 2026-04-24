<script setup>
 import { computed, ref } from 'vue';
 import Card from 'primevue/card';
 import Tag from 'primevue/tag';
 import ProgressBar from 'primevue/progressbar';
 import InputSwitch from 'primevue/inputswitch';
 import Tooltip from 'primevue/tooltip';
 import Button from 'primevue/button';
 import GatewayLogo from '@/Components/GatewayLogo.vue';
 import PaymentDetail from '@/Components/PaymentDetail.vue';
 import TableActionsDropdown from '@/Components/Table/TableActionsDropdown.vue';
 import TableAction from '@/Components/Table/TableAction.vue';
 import Menu from 'primevue/menu';
 import Divider from 'primevue/divider';

 const props = defineProps({
     detail: {
         type: Object,
         required: true
     },
     displayShortDetail: {
         type: Boolean,
         default: true
     },
     isAdminViewMode: {
         type: Boolean,
         default: false
     },
     isVipUser: {
         type: Boolean,
         default: false
     },
     currentTab: {
         type: String,
         default: 'active'
     },
     isTogglingActive: {
         type: Boolean,
         default: false
     },
     isArchiving: {
         type: Boolean,
         default: false
     }
 });

 const emit = defineEmits(['toggle-active', 'edit', 'archive', 'unarchive']);

 const menuRef = ref({});

 const setMenuRef = (el, id) => {
     if (el) {
         menuRef.value[id] = el;
     }
 };

 const toggleMenu = (event, id) => {
     menuRef.value[id]?.toggle(event);
 };

 const toggleActive = () => {
     emit('toggle-active', props.detail.id);
 }

 const editDetail = () => {
     emit('edit', props.detail);
 }

 const archiveDetail = () => {
     emit('archive', props.detail);
 }

 const unarchiveDetail = () => {
      emit('unarchive', props.detail);
 }

 const getActionMenuItems = (detail) => {
     const items = [];
     if (props.currentTab === 'active') {
         items.push({
             label: 'Редактировать',
             icon: 'pi pi-pencil',
             command: editDetail
         });
         items.push({
             label: 'Архивировать',
             icon: props.isArchiving ? 'pi pi-spin pi-spinner' : 'pi pi-inbox',
             command: archiveDetail,
             disabled: props.isArchiving
         });
     } else {
         items.push({
             label: 'Вернуть из архива',
             icon: props.isArchiving ? 'pi pi-spin pi-spinner' : 'pi pi-box',
             command: unarchiveDetail,
             disabled: props.isArchiving
         });
     }
     return items;
 };

 </script>

 <template>
     <Card class="payment-detail-card h-full relative">
         <template #header>
             <div class="flex justify-between items-center p-3 bg-surface-50 dark:bg-surface-700 rounded-t-lg">
                 <div class="flex flex-grow items-center">
                     <Button 
                         :class="['status-button', detail.is_active ? 'p-button-success' : 'p-button-danger']"
                         :label="detail.is_active ? 'Включен' : 'Выключен'"
                         :icon="detail.is_active ? 'pi pi-check' : 'pi pi-times'"
                         @click="toggleActive" 
                         :disabled="isTogglingActive || currentTab === 'archived'"
                         :loading="isTogglingActive" 
                         size="small"
                     />
                     <div class="ml-3">
                        <div class="flex items-left  flex-col justify-between align-items-left md:flex-row md:items-center md:gap-2"> 
                         
                             <span class="text-gray-400 dark:text-gray-400 text-xs md:text-base">#{{ detail.id }}</span>
                             
                         <span class="  text-gray-800 font-medium dark:text-gray-100 text-xs md:text-base">{{ detail.name }}</span>
                         </div>
                     </div>
                 </div>
                 <div>
                     <Menu 
                         :id="`menu_${detail.id}`" 
                         :model="getActionMenuItems(detail)" 
                         :popup="true" 
                         class="action-menu"
                         :ref="el => setMenuRef(el, detail.id)"
                         :pt="{
                             menu: { class: 'admin-dropdown-menu z-10' }
                         }"
                     />
                     <Button 
                         icon="pi pi-ellipsis-v" 
                         @click="(event) => toggleMenu(event, detail.id)"
                         class="p-button-rounded p-button-text p-button-sm context-menu-btn"
                         aria-haspopup="true"
                         aria-controls="overlay_menu" 
                     />
                 </div>
             </div>
         </template>
         <template #content>
             <div class="p-3">
                 <!-- Информация о реквизите -->
                 <div class="mb-4">
                     <div class="flex items-center gap-3 mb-4">
                          <GatewayLogo :img_path="detail.payment_gateway.logo_path" :name="detail.payment_gateway.name" class="w-10 h-10 text-gray-500 dark:text-gray-400"/>
                          <div>
                              <div class="text-gray-900 dark:text-gray-200 font-medium text-lg">{{ detail.detail }}</div>
                              <div class="text-sm text-gray-500">{{ detail.payment_gateway.name }}</div>
                          </div>
                     </div>
                 </div>

                 <Divider />

                 <!-- Детали и метрики -->
                 <div class="grid grid-cols-2 gap-4 mb-3">
                     <div>
                         <div class="text-sm text-gray-500 mb-1">Устройство</div>
                         <div class="dark:bg-gray-700 bg-gray-200 text-gray-800 dark:text-gray-300 inline-block rounded px-2 py-1 text-sm">
                             {{ detail.device_name }}
                         </div>
                     </div>
                     
                     <div class="col-6">
                         <div class="text-sm text-gray-500 mb-1">Сделок</div>
                         <div class="dark:bg-gray-700 bg-gray-200 dark:text-gray-300 inline-block rounded px-2 py-1 text-sm">
                             {{ detail.pending_orders_count }}/{{ detail.max_pending_orders_quantity }}
                         </div>
                     </div>
                     
                     <div>
                         <div class="text-sm text-gray-500 mb-1">Интервал</div>
                         <div class="dark:bg-gray-700 bg-gray-200 dark:text-gray-300 inline-block rounded px-2 py-1 text-sm">
                             {{ detail.order_interval_minutes !== null ? `${detail.order_interval_minutes} мин` : '-' }}
                         </div>
                     </div>
                     
                     <div>
                         <div class="text-sm text-gray-500 mb-1">Дневной лимит</div>
                         <div class="dark:text-gray-300 text-gray-800  text-sm text-right ">
                             {{ detail.current_daily_limit || 0 }} / {{ detail.daily_limit || 10000 }}
                             <div class="bg-gray-700 w-full h-1.5 rounded-full overflow-hidden mt-1">
                                 <div 
                                     class="bg-primary h-full rounded-full" 
                                     :style="{width: `${Math.min(100, ((detail.current_daily_limit || 0) / (detail.daily_limit || 10000)) * 100)}%`}"
                                 ></div>
                             </div>
                         </div>
                     </div>
                 </div>

                 <div v-if="isAdminViewMode || isVipUser" class="grid grid-cols-2 gap-4">
                     <div>
                         <div class="text-sm text-gray-500 mb-1">Мин. лимит</div>
                         <div class="text-gray-900 dark:text-gray-200">
                             {{ detail.min_order_amount !== null ? detail.min_order_amount : '∞' }}
                         </div>
                     </div>
                     
                     <div class="col-6">
                         <div class="text-sm text-gray-500 mb-1">Макс. лимит</div>
                         <div class="text-gray-900 dark:text-gray-200">
                             {{ detail.max_order_amount !== null ? detail.max_order_amount : '∞' }}
                         </div>
                     </div>
                 </div>
<div v-if="isAdminViewMode" class="grid grid-cols-2 gap-4"> 
                 <div class="mt-3">
                     <div class="text-sm text-gray-500 mb-1">Трейдер</div>
                     <div class="text-gray-900 dark:text-gray-200">{{ detail.owner_email }}</div>
                 </div>

 
                          
                             <Tag v-if="detail.is_external" severity="info" value="Внешний" class="text-xs"></Tag>
                      
</div>

             </div>
         </template>
     </Card>
 </template>

 <style scoped>
 </style> 