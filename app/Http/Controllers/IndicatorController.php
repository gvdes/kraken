<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Form;
use App\Models\FormResponsible;
use App\Models\FormType;
use App\Models\FormQuestion;
use App\Models\QuestionType;
use App\Models\QuestionOption;
use App\Models\QuestionResponse;
use App\Models\FormResponse;
use App\Models\User;
use App\Models\Store;

class IndicatorController extends Controller
{
    public function index(){

    }

    public function getForms(){
        $forms = Form::with('type','responsible','user')->get();
        $responsibles = FormResponsible::all();
        $types = FormType::all();
        $res = [
            "forms"=>$forms,
            "responsibles"=>$responsibles,
            "types"=>$types
        ];
        return response()->json($res,200);
    }

    public function addForm(Request $request){
        $uid = $request->fixeds->uid;
        $form = $request->all();
        $newform =  new Form();
        $newform->_created_by = $uid;
        $newform->name = $form['name'];
        $newform->description = $form['description'];
        $newform->_type = $form['_type']['id'];
        $newform->_responsible = $form['_responsible']['id'];
        $newform->save();
        $res = $newform->load(['type','responsible','user']);
        if($res){
            return response()->json($res);
        }else{
            return response()->json('Hubo un problema con la creacion',500);
        }
    }

    public function getForm($form){
        $getform = Form::with('type','responsible','user','question.type','question.options')->where('id',$form)->first();
        $typequestion = QuestionType::all();
        $res= [
            "form"=>$getform,
            "typequestion"=>$typequestion
        ];
        return response()->json($res);
    }

    public function addQuestion(Request $request){
        $form = $request->_form;
        $question= $request->question;
        $type = $request->type['id'];
        $required = $request->_required;

        $newQuestion = new FormQuestion();
        $newQuestion->_form = $form;
        $newQuestion->question = $question;
        $newQuestion->_type = $type;
        $newQuestion->_required = $required;
        $newQuestion->_breach = $request->_breach;
        $newQuestion->save();
        $res = $newQuestion->load(['type','options']);
        if($res){
            return response()->json($res);
        }else{
            return response()->json('Hubo un problema con la creacion');
        }

    }

    public function editQuest(Request $request){
        // return $request->all();
        $id = $request->id;
        $question = FormQuestion::find($id);
        $question->question = $request->question;
        $question->_required = $request->_required;
        $question->_type = $request->type['id'];
        $question->_points = isset($request->_points) ? $request->_points : null;
        $question->_breach =  isset($request->_breach) ? $request->_breach : 0;
        $question->save();
        $options = $request->options;
        $conditions = $request->condition;
        $matchedCond = [];
        if(count($options) > 0){
            foreach($options as $option){
                $resop [] = $option['option'];
                $opts = QuestionOption::updateOrCreate(
                    ['_question'=>$id,'option'=>$option['option']],
                    ['_question'=>$id,'option'=>$option['option'],'condition'=>$option['condition'],'_correct'=>isset($option['_correct']) ? $option['_correct'] : 0]);

            }
            $delopt = QuestionOption::where('_question', $id)
            ->whereNotIn('option', $resop)
            ->get(); // Obtiene las opciones a eliminar

            // Ahora se eliminan los registros encontrados
            $delopt->each(function($option) {
            $option->delete();
            });
            $question->load(['type','options']);
            return response()->json($question,200);
        }else{
            $question->load(['type']);
           return response()->json($question,200);
        }
    }

    public function deleteQuest(Request $request){
        $id = $request->id;
        $response = QuestionResponse::where('_question',$id)->get();
        if(count($response) == 0){
            $delOpt = QuestionOption::where('_question',$id)->delete();
            $delQues = FormQuestion::find($id)->delete();
            $res = [
                "delete"=>true,
                "message"=>'Se elimino con exito'
            ];
            return response()->json($res,200);
        }else{
            $res = [
                "delete"=>false,
                "message"=>'No se puede eliminar la pregunta, cuenta con respuestas'
            ];
            return response()->json($res,200);
        }
    }

    // public function getFormResp(Request $request, $sid ,$form){
    //     $date = now()->format('Y-m-d');
    //     $month = now()->format('m');
    //     // return $month;
    //     $uis = $request->fixeds;
    //     $userForm = User::with(['rol.area','store'])->where('id',$uis->uid)->first();
    //     $store = Store::find($sid);
    //     $getform = Form::with('type','responsible','user','question.type','question.options')->where('id',$form)->first();
    //     $users = User::all();
    //     if($users){
    //         if($getform->_type == 1 || $getform->_type == 2) {
    //             $responses = FormResponse::where([['_form',$getform->id],['_store',$sid]])->whereDate('created_at', $date)->get();
    //         }else if($getform->_type == 3){
    //             $responses = FormResponse::where([['_form',$getform->id],['_user',$uis->uid]])->whereMonth('created_at', $month)->get();
    //         }
    //         if(count($responses) >= 1){
    //             return response()->json('Ya se respondio este formulario ya no esta disponible',401);
    //         }else{
    //             if($getform->_active == 1){
    //                 if(in_array($uis->rol,[9,10,41]) && $getform->_responsible == 3 || $getform->_responsible == 1){
    //                     if($store->_type == 1 ){
    //                         if($getform->_type == 2 || $getform->_type == 3){
    //                             $res = [
    //                                 "usuarios"=>$users,
    //                                 "formulario"=>$getform,
    //                             ];
    //                             return response()->json($res,200);
    //                         }else{
    //                             return response()->json('No correspondes a la sucursal de el formulario',401);
    //                         }
    //                     }else{
    //                         if($getform->_type == 1 || $getform->_type == 3){
    //                             $res = [
    //                                 "usuarios"=>$users,
    //                                 "formulario"=>$getform,
    //                             ];
    //                             return response()->json($res,200);
    //                         }else{
    //                             return response()->json('No correspondes a la sucursal de el formulario',401);
    //                         }
    //                     }
    //                 } else if(in_array($userForm->rol['area']['id'],[1, 5, 7, 8]) && $getform->_responsible == 3 || $getform->_responsible == 1 || $getform->_responsible == 2){
    //                     $res = [
    //                         "usuarios"=>$users,
    //                         "formulario"=>$getform,
    //                     ];
    //                     return response()->json($res,200);
    //                 }else if($getform->_responsible == 1){
    //                     $res = [
    //                         "usuarios"=>$users,
    //                         "formulario"=>$getform,
    //                     ];
    //                     return response()->json($res,200);
    //                 }else{
    //                     return response()->json('No puedes responder este formulario',401);
    //                 }
    //             }else{
    //                 return response()->json('No esta disponible el formulario',401);
    //             }
    //         }
    //     }else{
    //         return response()->json("No hay ningun Usuario",404);
    //     }
    // }

    public function getFormResp(Request $request, $sid, $form){
        $date = now()->format('Y-m-d');
        $month = now()->format('m');
        $uis = $request->fixeds;

        // Obtener información del usuario y la tienda
        $userForm = User::with(['rol', 'store'])->where('id', $uis->uid)->first();
        $store = Store::find($sid);
        $getform = Form::with('type', 'responsible', 'user', 'question.type', 'question.options')->where('id', $form)->first();
        $users = User::all();

        if (!$users) {
            return response()->json("No hay ningún usuario", 404);
        }

        // Verificar si el formulario ya fue respondido
        $responses = [];
        if ($getform->_type == 1 || $getform->_type == 2) {
            $responses = FormResponse::where([['_form', $getform->id], ['_store', $sid]])->whereDate('created_at', $date)->get();
        } elseif ($getform->_type == 3) {
            $responses = FormResponse::where([['_form', $getform->id], ['_user', $uis->uid]])->whereMonth('created_at', $month)->get();
        }

        if (count($responses) >= 1) {
            return response()->json('Ya se respondió este formulario, ya no está disponible', 401);
        }

        if ($getform->_active != 1) {
            return response()->json('El formulario no está disponible', 401);
        }

        // Lógica principal ajustada
        $userHierarchy = $userForm->rol['hierarchy'];
        $userTypeRol = $userForm->rol['type_rol'];

        if (in_array($userHierarchy, [1, 2 , 3])) {
            // Jerarquía 1 o 2
            if ($userTypeRol == 2) {
                // Tipo de rol 2
                if ($store->_type == 1 && in_array($getform->_type, [2, 3])) {
                    if (in_array($getform->_responsible, [1, 3])) {
                        return $this->formatResponse($users, $getform);
                    }
                } elseif (in_array($getform->_type, [1, 3])) {
                    if (in_array($getform->_responsible, [1, 3])) {
                        return $this->formatResponse($users, $getform);
                    }
                }
            } else {
                // Otros tipos de rol
                if ($getform->_responsible == 1 && $getform->_type == 3) {
                    return $this->formatResponse($users, $getform);
                }
            }
        } elseif ($userHierarchy == 0) {
            // Jerarquía 0
            if (in_array($getform->_responsible, [1, 2, 3])) {
                return $this->formatResponse($users, $getform);
            }
        }

        return response()->json('No puedes responder este formulario', 401);
    }

    private function formatResponse($users, $form){
        return response()->json([
            "usuarios" => $users,
            "formulario" => $form,
        ], 200);
    }

    public function changeStatus(Request $request){
        $form = $request->id;
        $status = $request->_active;
        $updform = FORM::find($form);
        $updform->_active = $status;
        $updform->save();
        if($updform){
            return response()->json(true,200);
        }else{
            return response()->json(false,401);
        }
    }

    public function addFile(Request $request){
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName =$request->idms."-".$file->getClientOriginalName();
            $file->move(public_path('multimedia'), $fileName);
            return response()->json(['message' => $fileName]);
        }
        return response()->json(['message' => $request->all()], 400);
    }

    public function addResponse(Request $request){
        // return $request->all();
        $user = $request->_user;
        $form = $request->_form;
        $store = $request->_store;
        $questions = $request->question;
        $response = new FormResponse();
        $response->_user = $user;
        $response->_form = $form;
        $response->_store = $store;
        $response->save();
        $response->fresh();
        if($response){
            foreach($questions as $index => $question){
                $questResp = new QuestionResponse();
                $questResp->_response = $response->id;
                $questResp->_question = $question['id'];
                $questResp->_option = isset($question['_option']) ? $question['_option'] : null;
                $questResp->condition = isset($question['_condition']) ? Json_encode($question['_condition']) : null;
                if(isset($question['evidence'])){
                    if ($request->hasFile("question.$index.evidence")) {
                        $folderName = uniqid();
                        $folderPath = public_path('multimedia/forms/' . $folderName);
                        // Crear el directorio si no existe
                        if (!file_exists($folderPath)) {
                            mkdir($folderPath, 0777, true);
                        }
                        $files = $request->file("question.$index.evidence"); // Accede a los archivos de 'evidence'
                        foreach ($files as $file) {
                            $fileName = $file->getClientOriginalName();
                            $file->move($folderPath, $fileName);
                        }
                        $questResp->text = $folderName;
                    }
                }else{
                    $questResp->text = $question['text'];
                }
                $questResp->save();
            }
            $response->load(['responses.question']);
            return response()->json($response,200);
        }else{
            return response()->json('Problemas al crear la respuesta', 500);
        }
    }

    public function changeQualified(Request $request){
        $form = Form::find($request->id);
        $form->_qualified = $request->_qualified;
        $form->save();
        $form->load(['type','responsible','user','question.type','question.options']);
        return response()->json($form,200);

    }
}
