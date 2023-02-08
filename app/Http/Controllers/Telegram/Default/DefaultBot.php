<?php

namespace App\Http\Controllers\Service\Telegram\Default;

use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

use App\Http\Controllers\Service\Telegram\Bot;
use App\Http\Controllers\Service\Telegram\BotCommands;
use App\Http\Controllers\Service\Telegram\BotFunctions;
use App\Http\Controllers\Service\Telegram\Ecommerce\EcommerceRender as Render;

class DefaultBot extends Controller{

    private $bot_config = 'ecommerce';
    private $user_role = 'customer';
    private $is_user = false;

    ///////////////////////////////
    // MAIN CONTROLLERS
    public function index(Request $request){
        try{
            if($request->isMethod('post')){
                $command = new BotCommands;
                $update = json_decode($request->getContent());
                $bot = new Bot($this->bot_config);
                //$bot->sendMessage('127622235', 'uu');

                if(isset($update)){
                    if(0){
                        sendTelegram('me', "<pre language='php'>".json_encode($update)."</pre>");
                    }

                    if(!empty($update->message)){
                        //$bot->sendAction($update->message->chat->id, 'typing');
                        $user = $command->checkUserStatus($update->message->chat->id);
                        if($update->message->chat->id == '564239726'){
                            $this->isMessage($update, $user);
                            //$this->renderMessage($update, $user);
                        }else{
                            $this->isMessage($update, $user);
                        }
                    }elseif(!empty($update->callback_query->data)){
                        //$bot->sendAction($update->callback_query->message->chat->id, 'typing');
                        $user = $command->checkUserStatus($update->callback_query->message->chat->id);
                        if($update->callback_query->message->chat->id == '564239726'){
                            $this->renderMessage($update, $user);
                        }else{
                            $this->isCallback($update, $user);
                        }
                    }elseif(!empty($update->inline_query)){
                        $this->isInlineSearch($update);
                    }
                }
            }
            return 1;
        }catch (Exception $exception){
            reportExceptionErrors($exception);
            return 0;
        }
    }

    public function setWebhook(){
        $command = new BotCommands;
        return $command->setWebhook('setWebhook', config('telegram.bot.'.$this->bot_config.'.token'), config('telegram.bot.'.$this->bot_config.'.callback'));
    }

    public function isInlineSearch($update){
        $bot = new Bot($this->bot_config);
        $command = new BotCommands;
        return $bot->answerInlineQuery($update->inline_query, $command->searchByProducts($update->inline_query->query));
    }

    ///////////////////////////////
    // ACTION BY MESSAGE TYPE
    public function renderMessage($update, $user){
        /*if(1){
        $bot = new Bot($this->bot_config);
        $command = new BotCommands;
        $function = new BotFunctions;
        $render = new Render($command->getUserLanguage($update->message->chat->id));

        // MESSAGE INFO
        $message = $update->message ?? NULL;
        $message_id = $message->message_id ?? NULL;
        $chat_id = $message->chat->id ?? NULL;
        $chat_type = $message->chat->type ?? NULL;
        $reply = $message->reply_to_message ?? NULL;
        $from_reply = $message->reply_to_message->from->username ?? NULL;

        $text = $message->text ?? NULL;
        $sticker = $message->sticker ?? NULL;
        $contact = $message->contact->phone_number ?? NULL;
        $location = $message->location ?? NULL;
        $location_lon = $message->location->longitude ?? NULL;
        $location_lat = $message->location->latitude ?? NULL;

        // USER INFO
        $user_id = $message->from->id ?? NULL;
        $user_name = $message->from->name ?? NULL;
        $user_username = $message->from->username ?? NULL;
        $user_is_bot = $message->from->is_bot ?? NULL;
        $via_bot = $message->via_bot->is_bot ?? NULL;

        // CHECK FUNCTIONS
        $user_exists = $command->checkUserExists($chat_id) ?? false;
        $recent = $command->checkUserRecent($chat_id) ?? 'empty';
        $back = $command->checkUserBack($chat_id) ?? 'empty';
        //$lang = $command->getUserLanguage($chat_id);
        }*/

        // MESSAGE TO COMMAND
        if($this->selectRequest($update, 'private', 'message', 'text', 'contains', '/start', ['auth' => true])){
            sendTelegram('me', 'Success');
        }
        return 1;

        $this->selectRequest($update, 'private', 'message', 'text', 'contains', '/start', ['auth' => true]);

        // IS PRIVATE CHAT
        if($chat_type == 'private'){

            if($user){
                if($this->chisset($text)){
                    if($recent == 'empty'){

                        // REPLY ANSWER TO USER
                        switch ($text) {
                            case '/developer':
                                return $bot->sendMessage($chat_id, '@Weboook');
                            break;
                            /*case '/test':
                                return $command->setBack($chat_id, 'checkout');
                                //return $bot->sendMessage($chat_id, $render->text('string_register_successfully'));
                                //return $bot->sendMessage($chat_id, (string) now()->timestamp);
                            break;*/
                            case '/start':
                                $command->storeUserDetails($chat_id, $message->from);
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('bot_restarted'), 1, $bot->removeKeyboard());
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('string_show_home'), 0, $bot->makeInline($render->inlineHome()));
                            break;
                            case '/restart':
                                $command->restartUser($chat_id);
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('bot_restarted'), 1, $bot->removeKeyboard());
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('string_show_home'), 0, $bot->makeInline($render->inlineHome()));
                            break;
                            case '/catalog':
$bot->sendFullMessage($chat_id, $message_id, $render->text('string_select_category'), 1, $bot->makeInline($render->inlineCategories()));
                            break;
                            case '/cart':
$bot->sendFullMessage($chat_id, $message_id, $render->showCart($chat_id), 1, $bot->makeInline($render->keyCart($chat_id)));
                            break;
                            case '/orders':
$bot->sendFullMessage($chat_id, $message_id, $render->showOrders($chat_id), 1, $bot->makeInline($render->keyOrders($chat_id)));
                            break;
                            case '/profile':
$bot->sendFullMessage($chat_id, $message_id, $render->showProfile($chat_id), 1, $bot->makeInline($render->keyProfile()));
                            break;
                            case '/settings':
$bot->sendFullMessage($chat_id, $message_id, $render->showSettings(), 1, $bot->makeInline($render->keySettings()));
                            break;
                            case '/rules':
$bot->sendFullMessage($chat_id, $message_id, $render->showPolicy(), 1, $bot->makeInline($render->keyBack()));
                            break;
                            case '/support':
$bot->sendFullMessage($chat_id, $message_id, $render->showSupport(), 1, $bot->makeInline($render->keyBack()));
                            break;
                            case '/contact':
$bot->sendFullMessage($chat_id, $message_id, $render->showContact(), 1, $bot->makeInline($render->keyBack()));
                            break;
                            case '/deleted_account':
                                $command->removeUser($chat_id);
                                $bot->sendMessage($chat_id, $render->text('account_deleted'));
                                return $bot->sendFullMessage($chat_id, $message_id, $render->showSelectLanguage(), 0, $bot->makeInline($render->keySelectLanguage()));
                            break;
                            default:
                                if(!$via_bot){
                                    // preg_replace('/[^0-9.]+/', '', explode("\n", $text)[4])
                                    return $bot->sendFullMessage($chat_id, $message_id, $render->text('string_text_search_result'), 1, $bot->makeInline($render->keySearchText($function->removeEmoji(strip_tags($text)))));
                                }else{
                                    $product_id = preg_replace('/[^0-9.]+/', '', explode("\n", $text)[4]) ?? NULL;
                                    if(isset($product_id)){
                                        //$bot->sendFullMessage();
                                        $bot->sendPhoto($chat_id, $render->showProductDetails($product_id), $render->showProductImage($product_id), $bot->makeInline($render->inlineShowProductByID($product_id, 1)));
                                        $bot->deleteMessage($chat_id, $message_id);
                                    }
                                }
                            break;
                        }

                    }else{

                        // CLEAR RECENT CACHE
                        if($text == '/restart'){
                            if($command->restartUserRecent($chat_id)){
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('bot_restarted'), 0, $bot->removeKeyboard());
                                return $bot->sendFullMessage($chat_id, $message_id, $render->text('string_show_home'), 0, $bot->makeInline($render->inlineHome()));
                            }
                        }

                        if($text == '/deleted_account'){
                            $command->removeUser($chat_id);
                            $bot->sendMessage($chat_id, $render->text('account_deleted'));
                            return $bot->sendFullMessage($chat_id, $message_id, $render->showSelectLanguage(), 0, $bot->makeInline($render->keySelectLanguage()));
                        }

                        //EDIT PROFILE RECENT
                        switch ($recent) {
                            case 'edit_name':
                                if($command->editProfile('new_value_text', $chat_id, 'name', $text)){
                                    $bot->sendMessage($chat_id, $render->text('success_your_name_successfully_changed'));
                                    if($back == 'settings'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showProfile($chat_id), 0, $bot->makeInline($render->keyProfile()));
                                    }elseif($back == 'checkout'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showAccountDetailsCheckout($chat_id), 0, $bot->makeInline($render->keyAccountDetailsCheckout()));
                                    }
                                    $command->setBack($chat_id);
                                }else{
                                    $bot->sendMessage($chat_id, $render->text('error_name_entered_incorrectly'));
                                }
                            break;
                            case 'edit_email':
                                if($command->editProfile('new_value_text', $chat_id, 'email', $text)){
                                    $bot->sendMessage($chat_id, $render->text('success_your_email_successfully_changed'));
                                    if($back == 'settings'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showProfile($chat_id), 0, $bot->makeInline($render->keyProfile()));
                                    }elseif($back == 'checkout'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showAccountDetailsCheckout($chat_id), 0, $bot->makeInline($render->keyAccountDetailsCheckout()));
                                    }
                                    return $command->setBack($chat_id);
                                }else{
                                    $bot->sendMessage($chat_id, $render->text('error_email_entered_incorrectly'));
                                }
                            break;
                            case 'edit_address':
                                if($command->editProfile('new_value_text', $chat_id, 'address', $text)){
                                    $bot->sendMessage($chat_id, $render->text('success_your_address_successfully_changed'));
                                    if($back == 'settings'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showProfile($chat_id), 0, $bot->makeInline($render->keyProfile()));
                                        return $command->setBack($chat_id);
                                    }elseif($back == 'checkout'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showDeliveryDetailsCheckout($chat_id), 0, $bot->makeInline($render->keyAccountDetailsCheckout()));
                                        return $command->setBack($chat_id);
                                    }elseif($back == 'check_checkout'){
                                        //
                                    }
                                }else{
                                    $bot->sendMessage($chat_id, $render->text('error_address_entered_incorrectly'));
                                }
                            break;
                            case 'edit_comment':
                                if($command->editProfile('new_value_text', $chat_id, 'comment', $text)){
                                    $bot->sendMessage($chat_id, $render->text('success_comment_successfully_changed'));
                                    if($back == 'checkout'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showDeliveryDetailsCheckout($chat_id), 0, $bot->makeInline($render->keyAccountDetailsCheckout()));
                                    }

                                    return $command->setBack($chat_id);
                                }else{
                                    $bot->sendMessage($chat_id, $render->text('error_comment_entered_incorrectly'));
                                }
                            break;
                            case 'edit_phone':
                                if($text != NULL && $function->clearNumber($text) != NULL){
                                    $bot->sendFullMessage($chat_id, $message_id, $render->text('success_your_phone_successfully_saved').$function->clearNumber($text), 0, $bot->removeKeyboard());
                                    $bot->sendMessage($chat_id, $render->text('string_enter_verify_code'));
                                    $command->storePhoneNumber($chat_id, $function->clearNumber($text));
                                }else{
                                    $bot->sendFullMessage($chat_id, $message_id, $render->text('string_type_your_phone'), 1, $bot->makeKeyboard($render->keySharePhoneNumber()));
                                }
                            break;
                            case 'confirm_phone':
                                if($function->clearVerifyCode($text) == TelegramUser::where('chat_id', $chat_id)->first()->verify_code){
                                    $bot->sendFullMessage($chat_id, $message_id, $render->text('success_your_phone_successfully_changed'), 0, $bot->removeKeyboard());
                                    $command->confirmVerifyCode($chat_id, $function->clearVerifyCode($text));
                                    $command->editProfile('new_value_text', $chat_id, 'phone', $function->clearNumber(TelegramUser::where('chat_id', $chat_id)->first()->number));
                                    if($back == 'settings'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showProfile($chat_id), 0, $bot->makeInline($render->keyProfile()));
                                        return $command->setBack($chat_id);
                                    }elseif($back == 'checkout'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showAccountDetailsCheckout($chat_id), 0, $bot->makeInline($render->keyAccountDetailsCheckout()));
                                        return $command->setBack($chat_id);
                                    }elseif($back == 'check_checkout'){
                                        //
                                    }
                                }else{
                                    $bot->sendMessage($chat_id, $render->text('error_verify_code'));
                                }
                            break;
                        }

                        // IF ISSET BACK AND MESSAGE
                        if($back == 'check_checkout'){
                            $check = $command->checkUserDetailsForOrder($chat_id);
                            if(isset($check) && !empty($check)){
                                switch($check){
                                    case 'phone':
                                        $bot->sendFullMessage($chat_id, $message_id, $render->text('string_type_your_phone'), 0, $bot->makeKeyboard($render->keySharePhoneNumber()));
                                        $bot->deleteMessage($chat_id, $message_id);
                                        $command->setBack($chat_id, 'check_checkout');
                                        $command->setRecent($chat_id, 'edit_phone');
                                    break;
                                    case 'address':
                                        $bot->sendMessage($chat_id, $render->text('string_type_your_address'));
                                        $bot->deleteMessage($chat_id, $message_id);
                                        $command->setBack($chat_id, 'check_checkout');
                                        $command->setRecent($chat_id, 'edit_address');
                                    break;
                                    default:
                                        if($check > 0){
                                            $bot->sendMessage($chat_id, $render->text('string_order_success_info'), 0);
                                            $bot->sendFullMessage($chat_id, $message_id, $render->showOrder($check), 0, $bot->makeInline($render->keyOrder($check)));
                                            $command->clearCart($chat_id);
                                            $bot->deleteMessage($chat_id, $message_id);
                                        }
                                    break;
                                }
                                return 1;
                                //$bot->showAlert($cb_id, $render->text('error'), 0);
                            }

                        }

                        return 1;

                    }
                }elseif($this->chisset($contact)){
                    if($recent == 'edit_phone'){
                        if($contact != NULL){

                            if($command->storePhoneNumber($chat_id, $function->clearNumber($contact))){
                                $command->editProfile('new_value_text', $chat_id, 'number', $function->clearNumber($contact));
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('success_your_phone_successfully_saved').$function->clearNumber($contact), 0, $bot->removeKeyboard());
                                //$bot->sendMessage($chat_id, $render->text('string_enter_verify_code'));
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('string_enter_verify_code'), 0, $bot->makeInline($render->keyResendVerifyCode()));
                            }else{
                                $bot->sendMessage($chat_id, $render->text('error_phone_number_is_not_valid').$function->clearNumber($contact));
                            }
                        }
                    }
                }elseif($this->chisset($location)){
                }elseif($this->chisset($sticker)){
                    return $bot->sendFullMessage($chat_id, $message_id, $render->text('dont_send_sticker'));
                }
                return 0;
            } // AUTH USER MESSAGE
            else{
                // START OR REMOVE NOT COMPLATED USER
                if($text == '/developer'){
                    return $bot->sendMessage($chat_id, '@UzSoftic');
                }
                if($text == '/start'){
                    if(!empty($recent)){
                        $command->removeNotCompletedUser($chat_id);
                    }
                    return $bot->sendFullMessage($chat_id, $message_id, $render->showSelectLanguage(), 1, $bot->makeInline($render->keySelectLanguage()));
                }

                // REMOVE NOT COMPLATED USER
                if($text == '/restart'){
                    if($command->removeNotCompletedUser($chat_id)){
                        $bot->sendMessage($chat_id, $render->text('bot_restarted'));
                    }else{
                        $bot->sendMessage($chat_id, $render->text('error_occurred'));
                    }
                    return $bot->sendFullMessage($chat_id, $message_id, $render->showSelectLanguage(), 1, $bot->makeInline($render->keySelectLanguage()));
                }

                if($text == '/deleted_account'){
                    $command->removeUser($chat_id);
                    $bot->sendMessage($chat_id, $render->text('account_deleted'));
                    return $bot->sendFullMessage($chat_id, $message_id, $render->showSelectLanguage(), 0, $bot->makeInline($render->keySelectLanguage()));
                }

                // START LOGIN (RECENT ACTIONS)
                switch ($recent) {
                    case 'required_name':
                        if(strlen($function->clearName($text)) > 8){
                            $command->storeUserName($chat_id, $function->clearName($text));
                            $bot->sendMessage($chat_id, $render->text('string_your_name').$function->clearName($text));
                            return $bot->sendFullMessage($chat_id, $message_id, $render->text('string_type_your_phone'), 1, $bot->makeKeyboard($render->keySharePhoneNumber()));
                        }else{
                            $bot->sendMessage($chat_id, $render->text('string_type_your_name'));
                        }
                    break;
                    case 'required_phone':
                        if($text != NULL && $function->clearNumber($text) != NULL){
                            if($command->storePhoneNumber($chat_id, $function->clearNumber($text))){
                                $command->editProfile('new_value_text', $chat_id, 'number', $function->clearNumber($text));
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('success_your_phone_successfully_saved').$function->clearNumber($contact), 0, $bot->removeKeyboard());
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('string_enter_verify_code'), 0, $bot->makeInline($render->keyResendVerifyCode()));
                                //$bot->sendMessage($chat_id, $render->text('string_enter_verify_code'));
                            }else{
                                $bot->sendMessage($chat_id, $render->text('error_phone_number_is_not_valid'));
                            }

                            /*$bot->sendMessage($chat_id, $render->text('success_your_phone_successfully_saved').$function->clearNumber($text));
                            $bot->sendFullMessage($chat_id, $message_id, $render->text('string_enter_verify_code'), 0, $bot->makeInline($render->keyResendVerifyCode()));
                            $command->storePhoneNumber($chat_id, $function->clearNumber($text));*/
                        }elseif($contact != NULL){
                                if($command->storePhoneNumber($chat_id, $function->clearNumber($contact))){
                                $command->editProfile('new_value_text', $chat_id, 'number', $function->clearNumber($text));
                                $bot->sendMessage($chat_id, $render->text('success_your_phone_successfully_saved'));
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('string_enter_verify_code'), 0, $bot->makeInline($render->keyResendVerifyCode()));
                            }else{
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('error_phone_number_is_not_valid'), 1);
                            }
                        }else{
                            //$bot->sendMessage($chat_id, 'Введите свой номер телефона (Например 90 123 45 67):');
                            $bot->sendMessage($chat_id, $text);
                            $bot->sendFullMessage($chat_id, $message_id, $render->text('string_type_your_phone'), 1, $bot->makeKeyboard($render->keySharePhoneNumber()));
                            //$bot->sendFullMessage($chat_id, $message_id, 'fast', $bot->makeInline($render->keySendPhoneNumber()));
                        }
                    break;
                    case 'confirm_phone':
                        if($function->clearVerifyCode($text) == TelegramUser::where('chat_id', $chat_id)->first()->verify_code){
                            $bot->sendFullMessage($chat_id, $message_id, $render->text('string_register_successfully'), 0, $bot->removeKeyboard());
                            $command->confirmVerifyCode($chat_id, $function->clearVerifyCode($text));
                            $bot->deleteMessage($chat_id, $message_id);
                        }else{
                            //$bot->sendMessage($chat_id, $render->text('error_verify_code'));
                            $bot->sendFullMessage($chat_id, $message_id, $render->text('error_verify_code'), 0, $bot->makeInline($render->keyResendVerifyCode()));
                        }
                    break;
                }
                return 0;

            } // GUEST USER MESSAGE

        }
        // IS GROUP CHAT
        elseif(0 && $chat_type == 'supergroup'){
            if(isset($text)){
                $bot->sendAction($update->message->chat->id, 'typing');
                sleep(5);
                switch ($text) {
                    case 'kimsan':
                        $bot->sendFullMessage($chat_id, $message_id, 'ozin kimsan');
                    break;
                    case 'google':
                        $bot->sendFullMessage($chat_id, $message_id, 'kotta xolenikidan');
                    break;
                    case 'yo':
                        $bot->sendFullMessage($chat_id, $message_id, 'ana endi kordn');
                    break;
                    case 'rostan':
                        $bot->sendFullMessage($chat_id, $message_id, 'man iwonmiman');
                    break;
                    case 'nma':
                        $bot->sendFullMessage($chat_id, $message_id, 'bor gruppadan cqb ket');
                    break;
                    case 'bot':
                        $bot->sendFullMessage($chat_id, $message_id, 'aldama');
                    break;
                    case 'nma disan':
                        $bot->sendFullMessage($chat_id, $message_id, 'yoqmasa gruppadan cqb ket');
                    break;
                    case 'kim':
                        $bot->sendFullMessage($chat_id, $message_id, 'san');
                    break;
                    case '😂':
                        $bot->sendFullMessage($chat_id, $message_id, 'tirjeyma');
                    break;
                    case '😕':
                        $bot->sendFullMessage($chat_id, $message_id, 'qiyweymasdan tori yoz');
                    break;
                    case '😳':
                        $bot->sendFullMessage($chat_id, $message_id, 'bot kormaganmisan');
                    break;
                    case '😔':
                        $bot->sendFullMessage($chat_id, $message_id, 'yotvol endi');
                    break;
                    case '😁':
                        $bot->sendFullMessage($chat_id, $message_id, 'tilla tiwin borakan');
                    break;
                    case '👌':
                        $bot->sendFullMessage($chat_id, $message_id, 'daminni ol');
                    break;
                    case '🥺nega':
                        $bot->sendFullMessage($chat_id, $message_id, 'yoqmayapsan');
                    break;
                    case '🤥balkm':
                        $bot->sendFullMessage($chat_id, $message_id, 'tori etvoti anu qz');
                    break;
                    case 'salom':
                        $bot->sendFullMessage($chat_id, $message_id, 'nma bu salom, asssalomu alaykum didi
                        ');
                    break;
                    case 'ok':
                        $bot->sendFullMessage($chat_id, $message_id, 'oklama');
                    break;
                    case 'da':
                        $bot->sendFullMessage($chat_id, $message_id, 'nma da da da');
                    break;
                    default:

                        if($via_bot){
                            return $bot->sendFullMessage($chat_id, $message_id, 'botni iwatma');
                        }elseif(strlen($text) > 20){
                            return $bot->sendFullMessage($chat_id, $message_id, 'kimsan');
                            return $bot->sendFullMessage($chat_id, $message_id, 'qattan bildin');
                            return $bot->sendFullMessage($chat_id, $message_id, 'kaltaro yozsen olasanmi');
                        }elseif(strlen($text) < 2){
                            return $bot->sendFullMessage($chat_id, $message_id, 'cunmadim ooo');
                        }else{
                            return $bot->sendFullMessage($chat_id, $message_id, $text);
                            //return $bot->sendFullMessage($chat_id, $message_id, 'hazillawma');
                        }

                    break;
                }
            }elseif(isset($sticker)){
               return $bot->sendFullMessage($chat_id, $message_id, 'san niftema bop tiqilma');
               //return $bot->sendFullMessage($chat_id, $message_id, 'stikker tawama');
            }
        }

        return 0;
    }

    public function isMessage($update, $user){
        if(1){
        $bot = new Bot($this->bot_config);
        $command = new BotCommands;
        $function = new BotFunctions;
        $render = new Render($command->getUserLanguage($update->message->chat->id));

        // MESSAGE INFO
        $message = $update->message ?? NULL;
        $message_id = $message->message_id ?? NULL;
        $chat_id = $message->chat->id ?? NULL;
        $chat_type = $message->chat->type ?? NULL;
        $reply = $message->reply_to_message ?? NULL;
        $from_reply = $message->reply_to_message->from->username ?? NULL;

        $text = $message->text ?? NULL;
        $sticker = $message->sticker ?? NULL;
        $contact = $message->contact->phone_number ?? NULL;
        $location = $message->location ?? NULL;
        $location_lon = $message->location->longitude ?? NULL;
        $location_lat = $message->location->latitude ?? NULL;

        // USER INFO
        $user_id = $message->from->id ?? NULL;
        $user_name = $message->from->name ?? NULL;
        $user_username = $message->from->username ?? NULL;
        $user_is_bot = $message->from->is_bot ?? NULL;
        $via_bot = $message->via_bot->is_bot ?? NULL;

        // CHECK FUNCTIONS
        $user_exists = $command->checkUserExists($chat_id) ?? false;
        $recent = $command->checkUserRecent($chat_id) ?? 'empty';
        $back = $command->checkUserBack($chat_id) ?? 'empty';
        //$lang = $command->getUserLanguage($chat_id);
        }

        // IS PRIVATE CHAT
        if($chat_type == 'private'){

            if($user){
                if($this->chisset($text)){
                    if($recent == 'empty'){

                        // REPLY ANSWER TO USER
                        switch ($text) {
                            case '/developer':
                                return $bot->sendMessage($chat_id, '@Weboook');
                            break;
                            /*case '/test':
                                return $command->setBack($chat_id, 'checkout');
                                //return $bot->sendMessage($chat_id, $render->text('string_register_successfully'));
                                //return $bot->sendMessage($chat_id, (string) now()->timestamp);
                            break;*/
                            case '/start':
                                $command->storeUserDetails($chat_id, $message->from);
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('bot_restarted'), 1, $bot->removeKeyboard());
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('string_show_home'), 0, $bot->makeInline($render->inlineHome()));
                            break;
                            case '/restart':
                                $command->restartUser($chat_id);
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('bot_restarted'), 1, $bot->removeKeyboard());
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('string_show_home'), 0, $bot->makeInline($render->inlineHome()));
                            break;
                            case '/catalog':
$bot->sendFullMessage($chat_id, $message_id, $render->text('string_select_category'), 1, $bot->makeInline($render->inlineCategories()));
                            break;
                            case '/cart':
$bot->sendFullMessage($chat_id, $message_id, $render->showCart($chat_id), 1, $bot->makeInline($render->keyCart($chat_id)));
                            break;
                            case '/orders':
$bot->sendFullMessage($chat_id, $message_id, $render->showOrders($chat_id), 1, $bot->makeInline($render->keyOrders($chat_id)));
                            break;
                            case '/profile':
$bot->sendFullMessage($chat_id, $message_id, $render->showProfile($chat_id), 1, $bot->makeInline($render->keyProfile()));
                            break;
                            case '/settings':
$bot->sendFullMessage($chat_id, $message_id, $render->showSettings(), 1, $bot->makeInline($render->keySettings()));
                            break;
                            case '/rules':
$bot->sendFullMessage($chat_id, $message_id, $render->showPolicy(), 1, $bot->makeInline($render->keyBack()));
                            break;
                            case '/support':
$bot->sendFullMessage($chat_id, $message_id, $render->showSupport(), 1, $bot->makeInline($render->keyBack()));
                            break;
                            case '/contact':
$bot->sendFullMessage($chat_id, $message_id, $render->showContact(), 1, $bot->makeInline($render->keyBack()));
                            break;
                            case '/deleted_account':
                                $command->removeUser($chat_id);
                                $bot->sendMessage($chat_id, $render->text('account_deleted'));
                                return $bot->sendFullMessage($chat_id, $message_id, $render->showSelectLanguage(), 0, $bot->makeInline($render->keySelectLanguage()));
                            break;
                            default:
                                if(!$via_bot){
                                    // preg_replace('/[^0-9.]+/', '', explode("\n", $text)[4])
                                    return $bot->sendFullMessage($chat_id, $message_id, $render->text('string_text_search_result'), 1, $bot->makeInline($render->keySearchText($function->removeEmoji(strip_tags($text)))));
                                }else{
                                    $product_id = preg_replace('/[^0-9.]+/', '', explode("\n", $text)[4]) ?? NULL;
                                    if(isset($product_id)){
                                        //$bot->sendFullMessage();
                                        $bot->sendPhoto($chat_id, $render->showProductDetails($product_id), $render->showProductImage($product_id), $bot->makeInline($render->inlineShowProductByID($product_id, 1)));
                                        $bot->deleteMessage($chat_id, $message_id);
                                    }
                                }
                            break;
                        }

                    }else{

                        // CLEAR RECENT CACHE
                        if($text == '/restart'){
                            if($command->restartUserRecent($chat_id)){
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('bot_restarted'), 0, $bot->removeKeyboard());
                                return $bot->sendFullMessage($chat_id, $message_id, $render->text('string_show_home'), 0, $bot->makeInline($render->inlineHome()));
                            }
                        }

                        if($text == '/deleted_account'){
                            $command->removeUser($chat_id);
                            $bot->sendMessage($chat_id, $render->text('account_deleted'));
                            return $bot->sendFullMessage($chat_id, $message_id, $render->showSelectLanguage(), 0, $bot->makeInline($render->keySelectLanguage()));
                        }

                        //EDIT PROFILE RECENT
                        switch ($recent) {
                            case 'edit_name':
                                if($command->editProfile('new_value_text', $chat_id, 'name', $text)){
                                    $bot->sendMessage($chat_id, $render->text('success_your_name_successfully_changed'));
                                    if($back == 'settings'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showProfile($chat_id), 0, $bot->makeInline($render->keyProfile()));
                                    }elseif($back == 'checkout'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showAccountDetailsCheckout($chat_id), 0, $bot->makeInline($render->keyAccountDetailsCheckout()));
                                    }
                                    $command->setBack($chat_id);
                                }else{
                                    $bot->sendMessage($chat_id, $render->text('error_name_entered_incorrectly'));
                                }
                            break;
                            case 'edit_email':
                                if($command->editProfile('new_value_text', $chat_id, 'email', $text)){
                                    $bot->sendMessage($chat_id, $render->text('success_your_email_successfully_changed'));
                                    if($back == 'settings'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showProfile($chat_id), 0, $bot->makeInline($render->keyProfile()));
                                    }elseif($back == 'checkout'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showAccountDetailsCheckout($chat_id), 0, $bot->makeInline($render->keyAccountDetailsCheckout()));
                                    }
                                    return $command->setBack($chat_id);
                                }else{
                                    $bot->sendMessage($chat_id, $render->text('error_email_entered_incorrectly'));
                                }
                            break;
                            case 'edit_address':
                                if($command->editProfile('new_value_text', $chat_id, 'address', $text)){
                                    $bot->sendMessage($chat_id, $render->text('success_your_address_successfully_changed'));
                                    if($back == 'settings'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showProfile($chat_id), 0, $bot->makeInline($render->keyProfile()));
                                        return $command->setBack($chat_id);
                                    }elseif($back == 'checkout'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showDeliveryDetailsCheckout($chat_id), 0, $bot->makeInline($render->keyAccountDetailsCheckout()));
                                        return $command->setBack($chat_id);
                                    }elseif($back == 'check_checkout'){
                                        //
                                    }
                                }else{
                                    $bot->sendMessage($chat_id, $render->text('error_address_entered_incorrectly'));
                                }
                            break;
                            case 'edit_comment':
                                if($command->editProfile('new_value_text', $chat_id, 'comment', $text)){
                                    $bot->sendMessage($chat_id, $render->text('success_comment_successfully_changed'));
                                    if($back == 'checkout'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showDeliveryDetailsCheckout($chat_id), 0, $bot->makeInline($render->keyAccountDetailsCheckout()));
                                    }

                                    return $command->setBack($chat_id);
                                }else{
                                    $bot->sendMessage($chat_id, $render->text('error_comment_entered_incorrectly'));
                                }
                            break;
                            case 'edit_phone':
                                if($text != NULL && $function->clearNumber($text) != NULL){
                                    $bot->sendFullMessage($chat_id, $message_id, $render->text('success_your_phone_successfully_saved').$function->clearNumber($text), 0, $bot->removeKeyboard());
                                    $bot->sendMessage($chat_id, $render->text('string_enter_verify_code'));
                                    $command->storePhoneNumber($chat_id, $function->clearNumber($text));
                                }else{
                                    $bot->sendFullMessage($chat_id, $message_id, $render->text('string_type_your_phone'), 1, $bot->makeKeyboard($render->keySharePhoneNumber()));
                                }
                            break;
                            case 'confirm_phone':
                                if($function->clearVerifyCode($text) == TelegramUser::where('chat_id', $chat_id)->first()->verify_code){
                                    $bot->sendFullMessage($chat_id, $message_id, $render->text('success_your_phone_successfully_changed'), 0, $bot->removeKeyboard());
                                    $command->confirmVerifyCode($chat_id, $function->clearVerifyCode($text));
                                    $command->editProfile('new_value_text', $chat_id, 'phone', $function->clearNumber(TelegramUser::where('chat_id', $chat_id)->first()->number));
                                    if($back == 'settings'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showProfile($chat_id), 0, $bot->makeInline($render->keyProfile()));
                                        return $command->setBack($chat_id);
                                    }elseif($back == 'checkout'){
                                        $bot->sendFullMessage($chat_id, $message_id, $render->showAccountDetailsCheckout($chat_id), 0, $bot->makeInline($render->keyAccountDetailsCheckout()));
                                        return $command->setBack($chat_id);
                                    }elseif($back == 'check_checkout'){
                                        //
                                    }
                                }else{
                                    $bot->sendMessage($chat_id, $render->text('error_verify_code'));
                                }
                            break;
                        }

                        // IF ISSET BACK AND MESSAGE
                        if($back == 'check_checkout'){
                            $check = $command->checkUserDetailsForOrder($chat_id);
                            if(isset($check) && !empty($check)){
                                switch($check){
                                    case 'phone':
                                        $bot->sendFullMessage($chat_id, $message_id, $render->text('string_type_your_phone'), 0, $bot->makeKeyboard($render->keySharePhoneNumber()));
                                        $bot->deleteMessage($chat_id, $message_id);
                                        $command->setBack($chat_id, 'check_checkout');
                                        $command->setRecent($chat_id, 'edit_phone');
                                    break;
                                    case 'address':
                                        $bot->sendMessage($chat_id, $render->text('string_type_your_address'));
                                        $bot->deleteMessage($chat_id, $message_id);
                                        $command->setBack($chat_id, 'check_checkout');
                                        $command->setRecent($chat_id, 'edit_address');
                                    break;
                                    default:
                                        if($check > 0){
                                            $bot->sendMessage($chat_id, $render->text('string_order_success_info'), 0);
                                            $bot->sendFullMessage($chat_id, $message_id, $render->showOrder($check), 0, $bot->makeInline($render->keyOrder($check)));
                                            $command->clearCart($chat_id);
                                            $bot->deleteMessage($chat_id, $message_id);
                                        }
                                    break;
                                }
                                return 1;
                                //$bot->showAlert($cb_id, $render->text('error'), 0);
                            }

                        }

                        return 1;

                    }
                }elseif($this->chisset($contact)){
                    if($recent == 'edit_phone'){
                        if($contact != NULL){

                            if($command->storePhoneNumber($chat_id, $function->clearNumber($contact))){
                                $command->editProfile('new_value_text', $chat_id, 'number', $function->clearNumber($contact));
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('success_your_phone_successfully_saved').$function->clearNumber($contact), 0, $bot->removeKeyboard());
                                //$bot->sendMessage($chat_id, $render->text('string_enter_verify_code'));
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('string_enter_verify_code'), 0, $bot->makeInline($render->keyResendVerifyCode()));
                            }else{
                                $bot->sendMessage($chat_id, $render->text('error_phone_number_is_not_valid').$function->clearNumber($contact));
                            }
                        }
                    }
                }elseif($this->chisset($location)){
                }elseif($this->chisset($sticker)){
                    return $bot->sendFullMessage($chat_id, $message_id, $render->text('dont_send_sticker'));
                }
                return 0;
            } // AUTH USER MESSAGE
            else{
                // START OR REMOVE NOT COMPLATED USER
                if($text == '/developer'){
                    return $bot->sendMessage($chat_id, '@UzSoftic');
                }
                if($text == '/start'){
                    if(!empty($recent)){
                        $command->removeNotCompletedUser($chat_id);
                    }
                    return $bot->sendFullMessage($chat_id, $message_id, $render->showSelectLanguage(), 1, $bot->makeInline($render->keySelectLanguage()));
                }

                // REMOVE NOT COMPLATED USER
                if($text == '/restart'){
                    if($command->removeNotCompletedUser($chat_id)){
                        $bot->sendMessage($chat_id, $render->text('bot_restarted'));
                    }else{
                        $bot->sendMessage($chat_id, $render->text('error_occurred'));
                    }
                    return $bot->sendFullMessage($chat_id, $message_id, $render->showSelectLanguage(), 1, $bot->makeInline($render->keySelectLanguage()));
                }

                if($text == '/deleted_account'){
                    $command->removeUser($chat_id);
                    $bot->sendMessage($chat_id, $render->text('account_deleted'));
                    return $bot->sendFullMessage($chat_id, $message_id, $render->showSelectLanguage(), 0, $bot->makeInline($render->keySelectLanguage()));
                }

                // START LOGIN (RECENT ACTIONS)
                switch ($recent) {
                    case 'required_name':
                        if(strlen($function->clearName($text)) > 8){
                            $command->storeUserName($chat_id, $function->clearName($text));
                            $bot->sendMessage($chat_id, $render->text('string_your_name').$function->clearName($text));
                            return $bot->sendFullMessage($chat_id, $message_id, $render->text('string_type_your_phone'), 1, $bot->makeKeyboard($render->keySharePhoneNumber()));
                        }else{
                            $bot->sendMessage($chat_id, $render->text('string_type_your_name'));
                        }
                    break;
                    case 'required_phone':
                        if($text != NULL && $function->clearNumber($text) != NULL){
                            if($command->storePhoneNumber($chat_id, $function->clearNumber($text))){
                                $command->editProfile('new_value_text', $chat_id, 'number', $function->clearNumber($text));
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('success_your_phone_successfully_saved').$function->clearNumber($contact), 0, $bot->removeKeyboard());
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('string_enter_verify_code'), 0, $bot->makeInline($render->keyResendVerifyCode()));
                                //$bot->sendMessage($chat_id, $render->text('string_enter_verify_code'));
                            }else{
                                $bot->sendMessage($chat_id, $render->text('error_phone_number_is_not_valid'));
                            }

                            /*$bot->sendMessage($chat_id, $render->text('success_your_phone_successfully_saved').$function->clearNumber($text));
                            $bot->sendFullMessage($chat_id, $message_id, $render->text('string_enter_verify_code'), 0, $bot->makeInline($render->keyResendVerifyCode()));
                            $command->storePhoneNumber($chat_id, $function->clearNumber($text));*/
                        }elseif($contact != NULL){
                                if($command->storePhoneNumber($chat_id, $function->clearNumber($contact))){
                                $command->editProfile('new_value_text', $chat_id, 'number', $function->clearNumber($text));
                                $bot->sendMessage($chat_id, $render->text('success_your_phone_successfully_saved'));
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('string_enter_verify_code'), 0, $bot->makeInline($render->keyResendVerifyCode()));
                            }else{
                                $bot->sendFullMessage($chat_id, $message_id, $render->text('error_phone_number_is_not_valid'), 1);
                            }
                        }else{
                            //$bot->sendMessage($chat_id, 'Введите свой номер телефона (Например 90 123 45 67):');
                            $bot->sendMessage($chat_id, $text);
                            $bot->sendFullMessage($chat_id, $message_id, $render->text('string_type_your_phone'), 1, $bot->makeKeyboard($render->keySharePhoneNumber()));
                            //$bot->sendFullMessage($chat_id, $message_id, 'fast', $bot->makeInline($render->keySendPhoneNumber()));
                        }
                    break;
                    case 'confirm_phone':
                        if($function->clearVerifyCode($text) == TelegramUser::where('chat_id', $chat_id)->first()->verify_code){
                            $bot->sendFullMessage($chat_id, $message_id, $render->text('string_register_successfully'), 0, $bot->removeKeyboard());
                            $command->confirmVerifyCode($chat_id, $function->clearVerifyCode($text));
                            $bot->deleteMessage($chat_id, $message_id);
                        }else{
                            //$bot->sendMessage($chat_id, $render->text('error_verify_code'));
                            $bot->sendFullMessage($chat_id, $message_id, $render->text('error_verify_code'), 0, $bot->makeInline($render->keyResendVerifyCode()));
                        }
                    break;
                }
                return 0;

            } // GUEST USER MESSAGE

        }
        // IS GROUP CHAT
        elseif(0 && $chat_type == 'supergroup'){
            if(isset($text)){
                $bot->sendAction($update->message->chat->id, 'typing');
                sleep(5);
                switch ($text) {
                    case 'kimsan':
                        $bot->sendFullMessage($chat_id, $message_id, 'ozin kimsan');
                    break;
                    case 'google':
                        $bot->sendFullMessage($chat_id, $message_id, 'kotta xolenikidan');
                    break;
                    case 'yo':
                        $bot->sendFullMessage($chat_id, $message_id, 'ana endi kordn');
                    break;
                    case 'rostan':
                        $bot->sendFullMessage($chat_id, $message_id, 'man iwonmiman');
                    break;
                    case 'nma':
                        $bot->sendFullMessage($chat_id, $message_id, 'bor gruppadan cqb ket');
                    break;
                    case 'bot':
                        $bot->sendFullMessage($chat_id, $message_id, 'aldama');
                    break;
                    case 'nma disan':
                        $bot->sendFullMessage($chat_id, $message_id, 'yoqmasa gruppadan cqb ket');
                    break;
                    case 'kim':
                        $bot->sendFullMessage($chat_id, $message_id, 'san');
                    break;
                    case '😂':
                        $bot->sendFullMessage($chat_id, $message_id, 'tirjeyma');
                    break;
                    case '😕':
                        $bot->sendFullMessage($chat_id, $message_id, 'qiyweymasdan tori yoz');
                    break;
                    case '😳':
                        $bot->sendFullMessage($chat_id, $message_id, 'bot kormaganmisan');
                    break;
                    case '😔':
                        $bot->sendFullMessage($chat_id, $message_id, 'yotvol endi');
                    break;
                    case '😁':
                        $bot->sendFullMessage($chat_id, $message_id, 'tilla tiwin borakan');
                    break;
                    case '👌':
                        $bot->sendFullMessage($chat_id, $message_id, 'daminni ol');
                    break;
                    case '🥺nega':
                        $bot->sendFullMessage($chat_id, $message_id, 'yoqmayapsan');
                    break;
                    case '🤥balkm':
                        $bot->sendFullMessage($chat_id, $message_id, 'tori etvoti anu qz');
                    break;
                    case 'salom':
                        $bot->sendFullMessage($chat_id, $message_id, 'nma bu salom, asssalomu alaykum didi
                        ');
                    break;
                    case 'ok':
                        $bot->sendFullMessage($chat_id, $message_id, 'oklama');
                    break;
                    case 'da':
                        $bot->sendFullMessage($chat_id, $message_id, 'nma da da da');
                    break;
                    default:

                        if($via_bot){
                            return $bot->sendFullMessage($chat_id, $message_id, 'botni iwatma');
                        }elseif(strlen($text) > 20){
                            return $bot->sendFullMessage($chat_id, $message_id, 'kimsan');
                            return $bot->sendFullMessage($chat_id, $message_id, 'qattan bildin');
                            return $bot->sendFullMessage($chat_id, $message_id, 'kaltaro yozsen olasanmi');
                        }elseif(strlen($text) < 2){
                            return $bot->sendFullMessage($chat_id, $message_id, 'cunmadim ooo');
                        }else{
                            return $bot->sendFullMessage($chat_id, $message_id, $text);
                            //return $bot->sendFullMessage($chat_id, $message_id, 'hazillawma');
                        }

                    break;
                }
            }elseif(isset($sticker)){
               return $bot->sendFullMessage($chat_id, $message_id, 'san niftema bop tiqilma');
               //return $bot->sendFullMessage($chat_id, $message_id, 'stikker tawama');
            }
        }

        return 0;
    }

    public function isCallback($update, $user){
        $bot = new Bot($this->bot_config);
        $command = new BotCommands;
        $render = new Render($command->getUserLanguage($update->callback_query->message->chat->id));

        $cb_id = $update->callback_query->id ?? NULL;
        $cb_data = $update->callback_query->data ?? NULL;
        $cb_chat_id = $update->callback_query->message->chat->id ?? NULL;
        $cb_message_id = $update->callback_query->message->message_id ?? NULL;
        //$lang = $command->getUserLanguage($cb_chat_id);
        $recent = $command->checkUserRecent($cb_chat_id) ?? 'empty';
        $back = $command->checkUserBack($cb_chat_id) ?? 'empty';

        $var_cat = 'cb_cat_';
        $var_scat = 'cb_scat_';
        $var_sscat = 'cb_sscat_';

        if($user){

            switch ($cb_data) {
                case 'cb_homepage':
                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->text('string_show_home'), 0, $bot->makeInline($render->inlineHome()));
                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                break;
                case 'cb_categories':
                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->text('string_select_category'), 0, $bot->makeInline($render->inlineCategories()));
                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                break;
                case 'cb_profile':
                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showProfile($cb_chat_id), 0, $bot->makeInline($render->keyProfile()));
                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                break;
                case 'cb_settings':
                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showSettings(), 0, $bot->makeInline($render->keySettings()));
                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                break;
                case 'cb_cart':
                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showCart($cb_chat_id), 0, $bot->makeInline($render->keyCart($cb_chat_id)));
                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                break;
                case 'cb_orders':
                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showOrders($cb_chat_id), 0, $bot->makeInline($render->keyOrders($cb_chat_id)));
                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                break;
                case 'cb_checkout':
                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showCheckout($cb_chat_id), 0, $bot->makeInline($render->keyCheckout($cb_chat_id)));
                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                break;
                case 'cb_clearcart':
                    if($command->clearCart($cb_chat_id)){
                        $bot->showAlert($cb_id, $render->text('cart_emptied'));
                    }else{
                        $bot->showAlert($cb_id, $render->text('error_occurred'));
                    }
                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showCart($cb_chat_id), 0, $bot->makeInline($render->keyCart($cb_chat_id)));
                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                break;
                case 'cb_about':
                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showAbout(), 0, $bot->makeInline($render->keyBack()));
                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                break;
                case 'cb_rules':
                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showPolicy(), 0, $bot->makeInline($render->keyBack()));
                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                break;
                case 'cb_support':
                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showSupport(), 0, $bot->makeInline($render->keyBack()));
                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                break;
                case 'cb_callcenter':
                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showContact(), 0, $bot->makeInline($render->keyBack()));
                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                break;
                case 'cb_pricelist':
                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->text('button_pricelist'), 0, $bot->makeInline($render->keyPriceList()));
                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                break;
                case 'cb_createorder':
                    $check = $command->checkUserDetailsForOrder($cb_chat_id);
                    if(isset($check) && !empty($check)){
                        switch($check){
                            case 'phone':
                                $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->text('string_type_your_phone'), 0, $bot->makeKeyboard($render->keySharePhoneNumber()));
                                $bot->deleteMessage($cb_chat_id, $cb_message_id);
                                $command->setBack($cb_chat_id, 'check_checkout');
                                $command->setRecent($cb_chat_id, 'edit_phone');
                            break;
                            case 'address':
                                $bot->sendMessage($cb_chat_id, $render->text('string_type_your_address'));
                                $bot->deleteMessage($cb_chat_id, $cb_message_id);
                                $command->setBack($cb_chat_id, 'check_checkout');
                                $command->setRecent($cb_chat_id, 'edit_address');
                            break;
                            default:
                                if($check > 0){
                                    $bot->showAlert($cb_id, $render->text('string_order_success_alert'), 0);
                                    $bot->sendMessage($cb_chat_id, $render->text('string_order_success_info'));
                                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showOrder($check), 0, $bot->makeInline($render->keyOrder($check)));
                                    $command->clearCart($cb_chat_id);
                                    $command->setBack($cb_chat_id);
                                    $command->setRecent($cb_chat_id);
                                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                                }
                            break;
                        }
                        return 1;
                        //$bot->showAlert($cb_id, $render->text('error'), 0);
                    }
                break;
                case 'cb_checkout_use_coupon':
                    $bot->showAlert($cb_id, $render->text('being_developed'), 300);
                break;
                case 'cb_resendsmscode':
                    if($command->resendVerifyCode($cb_chat_id)){
                        $bot->showAlert($cb_id, $render->text('string_verify_resended_alert'), 0);
                        $bot->sendMessage($cb_chat_id, $render->text('string_verify_resended_message'));
                    }else{
                        $bot->showAlert($cb_id, $render->text('string_verify_wait'), 0);
                    }
                    return 1;
                break;
                default:
                    $ar = explode('_', $cb_data);
                    if(in_array('edit', $ar)){
                        $param = $ar[2];

                        $command->editProfile('callback', $cb_chat_id, $param, '');
                        switch ($param) {
                            case 'name':
                                $bot->sendMessage($cb_chat_id, $render->text('string_type_your_name'));
                                $command->setBack($cb_chat_id, 'settings');
                                $bot->deleteMessage($cb_chat_id, $cb_message_id); break;
                            case 'email':
                                $bot->sendMessage($cb_chat_id, $render->text('string_type_your_email'));
                                $command->setBack($cb_chat_id, 'settings');
                                $bot->deleteMessage($cb_chat_id, $cb_message_id); break;
                            case 'address':
                                $bot->sendMessage($cb_chat_id, $render->text('string_type_your_address'));
                                $command->setBack($cb_chat_id, 'settings');
                                $bot->deleteMessage($cb_chat_id, $cb_message_id); break;
                            case 'number':
                                //$bot->sendMessage($cb_chat_id, $render->text('string_type_your_phone'));
                                $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->text('string_type_your_phone'), 0, $bot->makeKeyboard($render->keySharePhoneNumber()));
                                $command->setBack($cb_chat_id, 'settings');
                                $bot->deleteMessage($cb_chat_id, $cb_message_id); break;
                            case 'phone':
                                //$bot->sendMessage($cb_chat_id, $render->text('string_type_your_phone'));
                                $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->text('string_type_your_phone'), 0, $bot->makeKeyboard($render->keySharePhoneNumber()));
                                $command->setBack($cb_chat_id, 'settings');
                                $bot->deleteMessage($cb_chat_id, $cb_message_id); break;
                            default:
                                $bot->sendMessage($cb_chat_id, $render->text('string_error_typing_details').json_encode($ar)); break;
                        }
                    }
                    if(in_array('cat', $ar)){ // cat -> scat
                        //$bot->sendMessage($cb_chat_id, $array[array_key_last($array)]);
                        $pcat = array_key_last($ar);
                        $bot->editMessageReplyMarkup($cb_chat_id, $cb_message_id, $bot->makeInline($render->inlineCategoryById($ar[$pcat]))); // cat
                    }
                    if(in_array('scat', $ar)){ // scat -> sscat
                        $pscat = array_key_last($ar); // cat - scat - sscat - page
                        $pcat = array_key_last($ar) - 1;
                        $bot->editMessageReplyMarkup($cb_chat_id, $cb_message_id, $bot->makeInline($render->inlineSubCategoryById($ar[$pcat], $ar[$pscat]))); // scat
                    }
                    if(in_array('sscat', $ar)){ // sscat -> products
                        $page = array_key_last($ar);
                        $psscat = array_key_last($ar) - 1;
                        $pscat = array_key_last($ar) - 2;
                        $pcat = array_key_last($ar) - 3;
                        if(in_array('back', $ar)){
                            $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->text('string_select_category'), 0, $bot->makeInline($render->inlineProductsList($ar[$pcat], $ar[$pscat], $ar[$psscat], $ar[$page])));
                            $bot->deleteMessage($cb_chat_id, $cb_message_id);
                        }else{
                            $bot->editMessageReplyMarkup($cb_chat_id, $cb_message_id, $bot->makeInline($render->inlineProductsList($ar[$pcat], $ar[$pscat], $ar[$psscat], $ar[$page])));
                        }

                    }
                    if(in_array('tocartinoneclick', $ar)){
                        $qty = (int)$ar[3];
                        $product_id = (int)$ar[2];

                        if($command->addProductToCart($cb_chat_id, $product_id, $qty)){
                            //$command->setBack('check_checkout');
                            $command->setBack($cb_chat_id, 'check_checkout');
                            $bot->showAlert($cb_id, $render->text('product_added_to_cart_successfully'));
                            $bot->sendMessage($cb_chat_id, $render->text('product_added_to_cart_successfully'));

                            $check = $command->checkUserDetailsForOrder($cb_chat_id);
                                if(isset($check) && !empty($check)){
                                    switch($check){
                                        case 'phone':
                                            $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->text('string_type_your_phone'), 0, $bot->makeKeyboard($render->keySharePhoneNumber()));
                                            $bot->deleteMessage($cb_chat_id, $cb_message_id);
                                            $command->setBack($cb_chat_id, 'check_checkout');
                                            $command->setRecent($cb_chat_id, 'edit_phone');
                                        break;
                                        case 'address':
                                            $bot->sendMessage($cb_chat_id, $render->text('string_type_your_address'));
                                            $bot->deleteMessage($cb_chat_id, $cb_message_id);
                                            $command->setBack($cb_chat_id, 'check_checkout');
                                            $command->setRecent($cb_chat_id, 'edit_address');
                                        break;
                                        default:
                                            if($check > 0){
                                                $bot->showAlert($cb_id, $render->text('string_order_success_alert'), 0);
                                                $bot->sendMessage($cb_chat_id, $render->text('string_order_success_info'));
                                                $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showOrder($check), 0, $bot->makeInline($render->keyOrder($check)));
                                                $command->clearCart($cb_chat_id);
                                                $command->setBack($cb_chat_id);
                                                $command->setRecent($cb_chat_id);
                                                $bot->deleteMessage($cb_chat_id, $cb_message_id);
                                            }
                                        break;
                                    }
                                    return 1;
                                }

                        }else{
                            $bot->showAlert($cb_id, $render->text('product_added_to_cart_error'));
                        }
                    }
                    if(in_array('tocart', $ar)){
                        $qty = (int)$ar[3];
                        $product_id = (int)$ar[2];

                        if($command->addProductToCart($cb_chat_id, $product_id, $qty)){
                            //$command->setBack('check_checkout');
                            $bot->showAlert($cb_id, $render->text('product_added_to_cart_successfully'));
                            //$bot->sendMessage($cb_chat_id, $render->text('product_added_to_cart_successfully'));

                        }else{
                            $bot->showAlert($cb_id, $render->text('product_added_to_cart_error'));
                        }

                    }
                    if(in_array('shop', $ar)){ // shop_product_{id}_{page} - sscat -> products
                        if(in_array('product', $ar) || in_array('productqty', $ar)){
                            if(in_array('productqty', $ar)){
                                $qty = $ar[6];

                                if(in_array('qtyminus', $ar)){
                                    if($qty > 1){
                                        $qty--;
                                    }
                                }elseif(in_array('qtyplus', $ar)){
                                    if($qty < 100){
                                        $qty++;
                                    }
                                }

                                $pprod = array_key_last($ar) - 1;
                                $psscat = array_key_last($ar) - 2;
                                $pscat = array_key_last($ar) - 3;
                                $pcat = array_key_last($ar) - 4;
                            }else{
                                $qty = 1;

                                $pprod = array_key_last($ar);
                                $psscat = array_key_last($ar) - 1;
                                $pscat = array_key_last($ar) - 2;
                                $pcat = array_key_last($ar) - 3;
                            }

                            //$bot->sendAction($cb_chat_id, 'upload_photo');
                            $bot->sendPhoto($cb_chat_id, $render->showProductDetails($ar[$pprod]), $render->showProductImage($ar[$pprod]), $bot->makeInline($render->inlineShowProduct($ar[$pcat], $ar[$pscat], $ar[$psscat], $ar[$pprod], $qty)) );
                            $bot->deleteMessage($cb_chat_id, $cb_message_id);
                        }elseif(in_array('category', $ar)){ // shop_category_{id}_{page}
                            //
                        }elseif(in_array('subcategory', $ar)){ // shop_subcategory_{id}_{page}
                            //
                        }elseif(in_array('subsubcategory', $ar)){ // shop_subsubcategory_{id}_{page}
                            //
                        }elseif(in_array('brand', $ar)){ // shop_brand_{id}_{page}
                            //
                        }elseif(in_array('seller', $ar)){ // shop_seller_{id}_{page}
                            //
                        }
                    }
                    if(in_array('productbyid', $ar)){ // sscat -> products
                        $qty = $ar[array_key_last($ar)];
                        $product_id = $ar[array_key_last($ar) - 1];
                        if(in_array('productqty', $ar)){

                            if(in_array('qtyminus', $ar)){
                                if($qty > 1){
                                    $qty--;
                                }
                            }elseif(in_array('qtyplus', $ar)){
                                if($qty < 100){
                                    $qty++;
                                }
                            }
                        }


                        //$bot->sendAction($cb_chat_id, 'upload_photo');
                        $bot->sendPhoto($cb_chat_id, $render->showProductDetails($product_id), $render->showProductImage($product_id), $bot->makeInline($render->inlineShowProductByID($product_id, $qty)));
                        $bot->deleteMessage($cb_chat_id, $cb_message_id);
                    }
                    if(in_array('product', $ar) || in_array('productqty', $ar)){ // sscat -> products
                        if(in_array('productqty', $ar)){
                            $qty = $ar[6];

                            if(in_array('qtyminus', $ar)){
                                if($qty > 1){
                                    $qty--;
                                }
                            }elseif(in_array('qtyplus', $ar)){
                                if($qty < 100){
                                    $qty++;
                                }
                            }

                            $pprod = array_key_last($ar) - 1;
                            $psscat = array_key_last($ar) - 2;
                            $pscat = array_key_last($ar) - 3;
                            $pcat = array_key_last($ar) - 4;
                        }else{
                            $qty = 1;

                            $pprod = array_key_last($ar);
                            $psscat = array_key_last($ar) - 1;
                            $pscat = array_key_last($ar) - 2;
                            $pcat = array_key_last($ar) - 3;
                        }

                        //$bot->sendAction($cb_chat_id, 'upload_photo');
                        $bot->sendPhoto($cb_chat_id, $render->showProductDetails($ar[$pprod]), $render->showProductImage($ar[$pprod]), $bot->makeInline($render->inlineShowProduct($ar[$pcat], $ar[$pscat], $ar[$psscat], $ar[$pprod], $qty)) );
                        $bot->deleteMessage($cb_chat_id, $cb_message_id);
                    }
                    if(in_array('orderbyid', $ar)){ // sscat -> products
                        $last = array_key_last($ar);
                        $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showOrder($ar[$last]), 0, $bot->makeInline($render->keyOrder()) );
                        $bot->deleteMessage($cb_chat_id, $cb_message_id);
                    }
                    if(in_array('lang', $ar)){
                        $code = array_key_last($ar);
                        $command->setLanguage($cb_chat_id, 'edit', $ar[$code]);
                        $render = new Render($ar[$code]);
                        sleep('1');
                        $bot->showAlert($cb_id, $render->text('language_changed'));
                        $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showSettings(), 0, $bot->makeInline($render->keySettings()));
                        return $bot->deleteMessage($cb_chat_id, $cb_message_id);
                        // Alert
                    }
                    if(in_array('alert', $ar)){
                        $code = array_key_last($ar);

                        switch ($ar[$code]) {
                            case 'paginationFirstpage':
                                $bot->showAlert($cb_id, $render->text('pagination_first_page'));
                            break;
                            case 'paginationLastpage':
                                $bot->showAlert($cb_id, $render->text('pagination_last_page'));
                            break;
                        }
                        return 1;
                    }
                    if(in_array('checkout', $ar)){ // cb_checkout_payment_show_listing cb_checkout_payment_change_method cb_checkout_payment_update_uzcard
                        $last = array_key_last($ar);
                        $method = array_key_last($ar) - 1;
                        $category = array_key_last($ar) - 2;
                        if($ar[$method] == 'show'){
                            switch ($ar[$category]) {
                                case 'account':
                                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showAccountDetailsCheckout($cb_chat_id), 0, $bot->makeInline($render->keyAccountDetailsCheckout()));
                                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                                break;
                                case 'delivery':
                                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showDeliveryDetailsCheckout($cb_chat_id), 0, $bot->makeInline($render->keyDeliveryDetailsCheckout()));
                                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                                break;
                                case 'payment':
                                    $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showPaymentDetailsCheckout($cb_chat_id), 0, $bot->makeInline($render->keyPaymentDetailsCheckout()));
                                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                                break;
                            }
                            //$bot->sendMessage($cb_chat_id, $render->text('string_error_typing_details'));
                            $bot->deleteMessage($cb_chat_id, $cb_message_id); break;
                        }elseif($ar[$method] == 'update'){
                            if($command->editProfile('new_value_text', $cb_chat_id, 'payment', $ar[$last])){
                                $bot->sendFullMessage($cb_chat_id, $cb_message_id, $render->showCheckout($cb_chat_id), 0, $bot->makeInline($render->keyCheckout($cb_chat_id)));
                                $bot->deleteMessage($cb_chat_id, $cb_message_id);
                            }else{
                                $bot->sendMessage($chat_id, $render->text('error_address_entered_incorrectly'));// TO ALERT
                            }
                        }else{
                            $command->editProfile('callback', $cb_chat_id, $ar[$last], '');
                            $command->setRecent('checkout');
                            switch ($ar[$last]) {
                                case 'name':
                                    $bot->sendMessage($cb_chat_id, $render->text('string_type_your_name'));
                                    $command->setBack($cb_chat_id, 'checkout');
                                    $bot->deleteMessage($cb_chat_id, $cb_message_id); break;
                                case 'email':
                                    $bot->sendMessage($cb_chat_id, $render->text('string_type_your_email'));
                                    $command->setBack($cb_chat_id, 'checkout');
                                    $bot->deleteMessage($cb_chat_id, $cb_message_id); break;
                                case 'address':
                                    $bot->sendMessage($cb_chat_id, $render->text('string_type_your_address'));
                                    $command->setBack($cb_chat_id, 'checkout');
                                    $bot->deleteMessage($cb_chat_id, $cb_message_id); break;
                                case 'phone':
                                    $bot->sendMessage($cb_chat_id, $render->text('string_type_your_phone'));
                                    $command->setBack($cb_chat_id, 'checkout');
                                    $bot->deleteMessage($cb_chat_id, $cb_message_id); break;
                                default:
                                    $bot->sendMessage($cb_chat_id, $render->text('string_error_typing_details').json_encode($ar)); break;
                            }
                        }
                        //$bot->deleteMessage($cb_chat_id, $cb_message_id);
                        return 0;
                    }
                break;
            } // AUTH USER CALLBACK

        }
        else{

            if(isset($cb_data)){
                $ar = explode('_', $cb_data);
                if($cb_data == 'cb_resendsmscode'){
                    if($command->resendVerifyCode($cb_chat_id)){
                        $bot->showAlert($cb_id, $render->text('string_verify_resended_alert'), 0);
                        $bot->sendMessage($cb_chat_id, $render->text('string_verify_resended_message'));
                    }else{
                        $bot->showAlert($cb_id, $render->text('string_verify_wait'), 0);
                    }
                }elseif(in_array('lang', $ar)){
                    $code = array_key_last($ar);
                    $act = array_key_last($ar) - 1;
                    //$code = array_key_last($ar);
                    if($command->setLanguage($cb_chat_id, $ar[$act], $ar[$code])){
                        $render = new Render($ar[$code]);
                        sleep('1');
                        $bot->sendMessage($cb_chat_id, $render->LanguageSelected($ar[$code]));
                    }

                    $bot->deleteMessage($cb_chat_id, $cb_message_id);
                    $bot->sendMessage($cb_chat_id, $render->text('type_your_name_and_surname').':');
                }
            }
            return 1;


        } // GUEST USER CALLBACK

        return 0;
    }

    ///////////////////////////////
    // HELPER FUNCTIONS
    public function selectRequest($update, $chat_type = 'private', $outpul_type = 'message', $message_type = 'text', $by_filter = 'contains', $is_in = NULL, $additional = []){
        //selectRequest($update, 'private', 'message', 'text', 'case');
        if($this->chisset($update->message)){
            $data = $update->message;
            //
        }elseif($this->chisset($update->callback_query)){
            $data = $update->callback_query;
            //
        }

        return 1;

        /*
            exists
            contains
        */

    }

    public function chisset($value){
        if(isset($value) && !empty($value)){
            return 1;
        }
        return 0;
    }
}
