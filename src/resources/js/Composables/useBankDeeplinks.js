import { computed } from 'vue';

// ВАЖНО: Эти конфигурации гипотетические и требуют тщательной проверки и адаптации!
const deeplinkConfigs = {
  tinkoff: {
    name: 'Т-Банк',
    scheme: 'tbank://',
    path: 'money_transfer',
    paramsMap: {
      recipient: 'cardnumber',
      amount: 'amount',
    },
  },
  sberbank: {
    name: 'СберБанк',
    scheme: 'sberbankonline://',
    path: 'money_transfer_to_card',
    paramsMap: {
      recipientAccount: 'cardNumber',
      amount: 'amount',
    },
  },
  alfabank: {
    name: 'Альфа-Банк',
    scheme: 'alfabank://',
    path: 'p2ptransfer',
    paramsMap: {
      recipient: 'card_number',
      amount: 'amount',
    },
  }
};

export function useBankDeeplinks(orderData) {
  // orderData - это ref или computed, содержащий props.data из PaymentLink/Index.vue

  const availableBanks = computed(() => {
    if (!orderData.value || !orderData.value.uuid) return []; // Добавим проверку на uuid
    return Object.keys(deeplinkConfigs).map(key => ({
      key: key,
      name: deeplinkConfigs[key].name,
      config: deeplinkConfigs[key] // Полная конфигурация, если понадобится в компоненте
    }));
  });

  const generateDeeplinkForBank = (bankKey) => {
    const config = deeplinkConfigs[bankKey];
    // Добавим более строгую проверку на orderData и его ключевые поля
    if (!config || !orderData.value || !orderData.value.uuid || !orderData.value.detail || !orderData.value.amount) { // Восстановлена проверка на detail и amount
        console.warn(`generateDeeplinkForBank: Недостаточно данных для генерации диплинка для банка ${bankKey}. orderData:`, orderData.value);
        return null;
    }

    // ---- УБИРАЕМ ТЕСТОВЫЙ РЕЖИМ ----
    // console.log(`generateDeeplinkForBank (TEST MODE - SCHEME ONLY): Схема для ${bankKey}: ${config.scheme}`);
    // return config.scheme; // Возвращаем только схему
    // ---- КОНЕЦ ТЕСТОВОГО РЕЖИМА ----

    // Возвращаем оригинальную логику
    const params = new URLSearchParams();
    const { detail, amount, uuid, bank_bik /* Это поле все еще гипотетическое */ } = orderData.value;

    for (const our_param_key in config.paramsMap) {
      const bank_param_key = config.paramsMap[our_param_key];
      let value;

      switch (our_param_key) {
        case 'recipient': // Общий ключ для номера карты/счета в paramsMap (Tinkoff, Alfa)
        case 'recipientAccount': // Специфичный ключ (Sberbank)
          value = detail;
          break;
        case 'amount':
          value = amount;
          break;
        // case 'purpose': // Удален, так как отсутствует в новых примерах
        //   value = `Оплата по заказу ${uuid}`;
        //   break;
        case 'recipientBik': // Пока оставляем, если вдруг понадобится, но в примерах его нет
          value = bank_bik;
          break;
        // Добавьте другие case по мере необходимости для специфичных параметров банков
      }

      if (value !== undefined && value !== null) {
        params.append(bank_param_key, String(value));
      }
    }

    return `${config.scheme}${config.path}?${params.toString()}`; // Восстановлена генерация полного URL
  };

  return {
    availableBanks,
    generateDeeplinkForBank,
  };
} 