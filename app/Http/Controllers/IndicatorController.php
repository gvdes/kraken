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
use App\Models\Classification;
use App\Models\StoreClassification;
use App\Models\ClassificationStore;
use App\Models\UserClassification;
use App\Models\ViewReportWeek;
use App\Models\User;
use App\Models\Store;
use App\Models\Fecha;
use Illuminate\Support\Facades\DB;

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
        $question->_retained = isset($request->_retained) ? $request->_retained : null;
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
        if ($getform->_type == 1) {
            $responses = FormResponse::where([['_form', $getform->id], ['_store', $sid]])->whereDate('created_at', $date)->get();
        } else if($getform->_type == 2){
            $responses = [];
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

        if (in_array($userHierarchy, [1, 2 , 3,4])) {// modificar para que solo muestre por rol
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

    public function getClass(){
        $classification = Classification::all();
        return response()->json($classification,200);
    }

    public function editClass(Request $request){
        $id = $request->id;
        $classification = Classification::find($id);
        $classification->percentage = $request->percentage;
        $classification->save();
        $classification->fresh();
        return response()->json($classification,200);
    }

    public function getClassStore(){
        $store = Store::with('classification.clasification.bonuses')
        ->wherehas('classification')
        ->get();
        $storeClass = ClassificationStore::with('bonuses')->get();

        $res = [
            "classifications"=>$storeClass,
            "stores"=>$store
        ];

        return response()->json($res,200);
    }

    public function editClassStore(Request $request){//edicion de clasificacion de la sucursal
            $class = StoreClassification::find($request->id);
            $class->_classification_store = $request->clasification['id'];
            $class->save();
            $res = $class->fresh();
            if($res){
                // return $class;
                $users = User::with(['classification.store.store','classification.store.clasification.bonuses','classification.classification','rol.area'])
                ->whereHas('rol', function($q) { $q->where('type_rol', 2)->whereIn('hierarchy',[2,3,4])->whereIn('_area',[2,3]);})
                // ->whereHas('rol', function($q) { $q->where('type_rol', 2)->whereIn('hierarchy',[2,3,4]);}) // para pruebas
                ->whereHas('classification',  function($q) use($class) { $q->where('_store_classification', $class->id);})->get();

                foreach($users as $user){
                    $bonus = collect($user->classification->store->clasification->bonuses)->firstWhere('_hierarchy', $user->rol->hierarchy);
                    if ($bonus) {
                        $updBon = UserClassification::where('_user',$user->id)->update(['import'=>$bonus['import']]);
                    }
                }
                $classStore = Store::with('classification.clasification.bonuses')->where('id', $request->id)->first();
                return response()->json($classStore,200);
            }else{
                return response()->json('No se realizo la modificacion',500);
            }

    }

    public function getUserClass() {
        // Optimizar la carga de datos de los usuarios
        $user = User::with([
                'classification.store.store',
                'classification.store.clasification.bonuses',
                'classification.classification',
                'rol.area'
            ])
            ->whereHas('rol', function($q) {
                $q->where('type_rol', 2)
                  ->whereIn('hierarchy', [2, 3, 4])
                  ->whereIn('_area', [2, 3]);
            })
            ->whereHas('classification')
            ->where('_state', '!=', 4)
            ->get();

        $clasisfications = Classification::all();

        $storesClass = StoreClassification::with(['store', 'clasification'])->get();

        $weekAct = Fecha::selectRaw('WEEK((fecha - INTERVAL (DAYOFWEEK(fecha) % 7) DAY), 7) as week, fecha')
            ->whereRaw('WEEK((fecha - INTERVAL (DAYOFWEEK(fecha) % 7) DAY), 7) = WEEK((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY), 7)
                        AND YEAR((fecha - INTERVAL (DAYOFWEEK(fecha) % 7) DAY)) = YEAR((CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) % 7) DAY))')
            ->orderBy('fecha', 'asc')
            ->get();

        $res = [
            "users" => $user,
            "classifications" => $clasisfications,
            "stores" => $storesClass,
            "week" => $weekAct
        ];

        return response()->json($res, 200);
    }

    public function editUserClass(Request $request){
        $user = $request->_user;
        $classification = $request->classification;
        $class = UserClassification::where('_user',$user)->update(['_classification'=> $classification['id']]);
        $user = User::with([
            'classification.store.clasification.bonuses',
            'classification.store.store',
            'classification.classification',
            'rol.area'
            ])->where('id',$user)->first();
        return response()->json($user,200);
    }

    public function editUserStore(Request $request){
        $id = $request->_user;
        $changeBonus = $request->chBonus;
        $store = $request->store;
        $class = UserClassification::where('_user',$id)->update(['_store_classification'=> $store['id']]);
        if($changeBonus){
            $users = User::with(['classification.store.store','classification.store.clasification.bonuses','classification.classification','rol.area'])
            ->where('id',$id)
            ->first();
            $bonus = collect($users->classification->store->clasification->bonuses)->firstWhere('_hierarchy', $users->rol->hierarchy);
            if ($bonus) {
                $updBon = UserClassification::where('_user',$id)->update(['import'=>$bonus['import']]);
            }
        }
        $user = User::with(['classification.store.store','classification.store.clasification.bonuses','classification.classification','rol.area'])->where('id',$id)->first();
        $user['class'] =  $this->getCalculateClassUser($user);
        return response()->json($user,200);
    }

    public function changeUserBonues(Request $request){
        $user = $request->_user;
        $import = $request->import;

        $changeValue = UserClassification::where('_user',$user)->update(['import'=>$import]);
        if($changeValue){
            return response()->json('Cambio de importe Realizado',200);
        }else{
            return response()->json('El monto es el mismo no se realizo cambio',200);
        }

    }

    public function getformResponses(Request $request){
        $to =  $request->to;
        $from =  $request->from;
        $forms = Form::all();
        // $response  = FormResponse::with('store','user','form')->get();
        $response  = FormResponse::with('store','user.rol.area','form')
        ->withCount(['responses as total_score' => function ($query) {
            $query->leftJoin('form_questions', 'question_responses._question', '=', 'form_questions.id')
                ->whereHas('selectedOption', function ($q) {
                    $q->where('_correct', 1);
                })
                ->select(DB::raw('COALESCE(SUM(form_questions._points), 0)'));
        }])
        ->whereDate('created_at','>=',$from)
        ->whereDate('created_at','<=',$to)
        ->get();
        $stores =  Store::all();
        $res = [
            "form"=>$forms,
            "responses"=>$response,
            "stores"=>$stores
        ];
        return response()->json($res,200);
    }

    public function viewResponseForm($id){
        $response  = FormResponse::with('responses.question.options','store','user','form')->where('id',$id)->first();
        $preguntas =  $response->responses;
        foreach($preguntas as $pregunta){
            $folderName = $pregunta['question']['_type'] == 3 ? $pregunta['text'] : false ;
            if($folderName){
                $folderPath = public_path("multimedia/forms/{$folderName}");
                if (!file_exists($folderPath) || !is_dir($folderPath)) {
                    $pregunta['files'] = [];
                }
                $files = array_values(array_diff(scandir($folderPath), ['.', '..'])); // Excluye `.` y `..`
                $filesWithUrls = array_map(function ($file) use ( $folderName) {
                    return  "forms/{$folderName}/{$file}";
                }, $files);
            $pregunta['files']= $filesWithUrls;
            }
        }
        $users = User::with('rol.area')->get();
        $res = [
            "responses"=>$response,
            "usuarios"=>$users
        ];

        return response()->json($res,200);
    }

    // public function getCalculateClassUser(Request $request) {
    //     $colab = $request->user;
    //     $inicio = $request->to;
    //     $final = $request->from;
    //     $year = $request->year;
    //     $report = collect(DB::select("CALL obtReport(?, ?, ?)", [$inicio, $final, $year]))
    //     ->where('ID', $colab['RC_id']);


    //     // return $report;
    //     // $report = ViewReportWeek::select(
    //     //     '*',
    //     //     DB::raw('faltas(LUNES) + faltas(MARTES) + faltas(MIERCOLES) + faltas(JUEVES) + faltas(VIERNES) + faltas(SABADO) + faltas(DOMINGO) AS FALTAS'),
    //     //     DB::raw('retardos(LUNES) + retardos(MARTES) + retardos(MIERCOLES) + retardos(JUEVES) + retardos(VIERNES) + retardos(SABADO) + retardos(DOMINGO) AS RETARDOS'),
    //     // )->where('id', $colab['RC_id'])->get();
    //     $userResponses =  $this->calculateResponsesClass($colab['id'],$inicio,$final,$year);

    //     // Obtener usuarios activos con roles específicos
    //     $user = User::where('id', $colab['id'])->first();
    //     if (!$user) {
    //         return null; // Si no se encuentra el usuario, retorna null
    //     }

    //     $userData = [
    //         "name" => $user->name . ' ' . $user->surnames,
    //         "id" => $user->id,
    //         "id_rc" => $user->RC_id
    //     ];

    //     // Procesar usuarios para generar el reporte
    //     $responses = $userResponses[$userData['id']] ?? [];
    //     $totalRetained = array_sum(array_column($responses, '_retained')) ?? 0;
    //     $faltas = floatval($report[0]['FALTAS'] ?? 0);
    //     $retardos = floatval($report[0]['RETARDOS'] ?? 0);
    //     $asist = ($faltas * 100 + $retardos * 20);

    //     $averageRetained = round((600 - ($totalRetained + $asist)) / 6);

    //     $classification = match (true) {
    //         $averageRetained >= 90 && $averageRetained <= 100 => 1,
    //         $averageRetained >= 80 && $averageRetained <= 89 => 2,
    //         $averageRetained >= 70 && $averageRetained <= 79 => 3,
    //         $averageRetained <= 69 => 4,
    //         default => 1
    //     };

    //     $res = [
    //         "asistencia"=>$report,
    //         "percentage" => $averageRetained,
    //         "classification" => $classification,
    //         "puntosAsistencia"=> $asist,
    //         "puntosChecklist" => $totalRetained,
    //         "responses" => $responses
    //     ];

    //     return response()->json($res);
    // }

    // private function calculateResponsesClass($userId,$inicio,$final,$year) {
    //     $weekCondition = "WEEK((created_at - INTERVAL (DAYOFWEEK(created_at) % 7) DAY), 7) BETWEEN  $inicio AND $final
    //     AND YEAR((created_at - INTERVAL (DAYOFWEEK(created_at) % 7) DAY)) = $year";

    //     $responses = QuestionResponse::with([
    //         'selectedOption',
    //         'question',
    //         'response' => function ($q) use ($weekCondition) {
    //             $q->whereRaw($weekCondition)
    //                 ->whereHas('form', function ($q) {
    //                     $q->where('_qualified', 1);
    //                 });
    //         },
    //         'response.form'
    //     ])
    //     ->whereHas('selectedOption', function ($q) {
    //         $q->where('_correct', 0);
    //     })
    //     ->whereHas('response', function ($q) use ($weekCondition) {
    //         $q->whereRaw($weekCondition)
    //             ->whereHas('form', function ($q) {
    //                 $q->where('_qualified', 1);
    //             });
    //     })
    //     ->get();

    //     $userResponses = [];

    //     foreach ($responses as $response) {
    //         $conditions = json_decode(json_decode($response['condition']));
    //         if (!$conditions) continue;

    //         $users = [];
    //         $quali = "";
    //         $observacion = "No hay observación";

    //         foreach ($conditions as $condition) {
    //             if (!isset($condition->response)) continue;

    //             if (is_string($condition->response)) {
    //                 $observacion = $condition->response;
    //                 continue;
    //             }

    //             if (is_array($condition->response)) {
    //                 foreach ($condition->response as $qualified) {
    //                     if (is_object($qualified) && isset($qualified->col)) {
    //                         $users[] = $qualified->col;
    //                         $quali = $qualified->qualified;
    //                     } elseif (is_numeric($qualified)) {
    //                         $users[] = $qualified;
    //                     }
    //                 }
    //             }
    //         }

    //         // 🔥 Filtrar solo si el usuario que buscamos ($userId) está en la lista de users
    //         if (!in_array($userId, $users)) {
    //             continue; // Saltar este response si el usuario no está en conditions
    //         }

    //         if (!isset($userResponses[$userId])) {
    //             $userResponses[$userId] = [];
    //         }

    //         $userResponses[$userId][] = [
    //             "response" => $response['id'],
    //             "form" => $response['response']['form']['name'] ?? "N/A",
    //             "question" => $response['question']['question'] ?? "N/A",
    //             "_retained" => $response['question']['_retained'] ?? 0,
    //             "text" => $response['text'] ?? "",
    //             "qualified" => $quali,
    //             "fecha_hora" => $response['response']['created_at']->format('Y-m-d H:i:s') ?? "N/A",
    //             "observacion" => $observacion
    //         ];
    //     }

    //     return $userResponses;
    // }



    public function getCalculateClassUser(Request $request) {
        $colab = $request->all();
        $date = now()->format('Y-m-d');
        $fechas = Fecha::select('*',
        DB::raw(' WEEK((fecha - INTERVAL (DAYOFWEEK(fecha) % 7) DAY), 7) AS week'),
        DB::raw(' YEAR(fecha) AS anio')
        )->orderBy('fecha','ASC')->get();
        $filtradas = $fechas->filter(fn($item) => $item->fecha == $date);
        $oing = $filtradas->last();
        $inicio = $oing->week;
        $final = $oing->week;
        $year = $oing->anio;
        $rawReport = collect(DB::select("CALL obtReport(?, ?, ?)", [$inicio, $final, $year]))
        ->where('ID', $colab['RC_id']);
        $user = User::find($colab['id']);
        if (!$user) {
            return null;
        }
        $userData = [
            "name" => $user->name . ' ' . $user->surnames,
            "id" => $user->id,
            "id_rc" => $user->RC_id
        ];
        $resumenSemanal = [];
        foreach ($rawReport as $rep) {
            $week = $rep->semana;
            $anio = $rep->ANIO;
            $faltas = floatval($rep->FALTAS ?? 0);
            $retardos = floatval($rep->RETARDOS ?? 0);
            $asist = ($faltas * 100 + $retardos * 20);
            $userResponses = $this->calculateResponsesClass($colab['id'], $week,  $anio);
            $totalRetained = array_sum(array_column($userResponses, '_retained')) ?? 0;
            $averageRetained = round((600 - ($totalRetained + $asist)) / 6);
            $classification = match (true) {
                $averageRetained >= 90 && $averageRetained <= 100 => 1,
                $averageRetained >= 80 && $averageRetained <= 89 => 2,
                $averageRetained >= 70 && $averageRetained <= 79 => 3,
                $averageRetained <= 69 => 4,
                default => 1
            };
            $resumenSemanal[$week] = [
                "percentage" => $averageRetained,
                "classification" => $classification,
                "puntosAsistencia" => $asist,
                "puntosChecklist" => $totalRetained,
                "responses" => $userResponses,
                "asistencia" => $rep,
            ];
        }
        return response()->json([
            "resumen" => $resumenSemanal,
            "fechas"=>$fechas,
        ]);
    }

    public function getCalculateClassUserFilter(Request $request) {
        // return $request->all();
        $colab = $request->user;
        $rawReport = collect(DB::select("CALL obtReport(?, ?, ?)", [$request->min,$request->max , $request->year]))
        ->where('ID', $colab['RC_id']);


        $user = User::find($colab['id']);
        if (!$user) {
            return null;
        }

        $userData = [
            "name" => $user->name . ' ' . $user->surnames,
            "id" => $user->id,
            "id_rc" => $user->RC_id
        ];

        $resumenSemanal = [];

        foreach ($rawReport as $rep) {
            $week = $rep->semana;
            $anio = $rep->ANIO;
            $faltas = floatval($rep->FALTAS ?? 0);
            $retardos = floatval($rep->RETARDOS ?? 0);
            $asist = ($faltas * 100 + $retardos * 20);
            $userResponses = $this->calculateResponsesClass($colab['id'], $week,  $anio);
            $totalRetained = array_sum(array_column($userResponses, '_retained')) ?? 0;
            $averageRetained = round((600 - ($totalRetained + $asist)) / 6);
            $classification = match (true) {
                $averageRetained >= 90 && $averageRetained <= 100 => 1,
                $averageRetained >= 80 && $averageRetained <= 89 => 2,
                $averageRetained >= 70 && $averageRetained <= 79 => 3,
                $averageRetained <= 69 => 4,
                default => 1
            };
            $resumenSemanal[$week] = [
                "percentage" => $averageRetained,
                "classification" => $classification,
                "puntosAsistencia" => $asist,
                "puntosChecklist" => $totalRetained,
                "responses" => $userResponses,
                "asistencia" => $rep,
            ];
        }
        return response()->json([
            "resumen" => $resumenSemanal
        ]);
    }

    private function calculateResponsesClass($userId, $semana, $year) {
        $weekCondition = "WEEK((created_at - INTERVAL (DAYOFWEEK(created_at) % 7) DAY), 7) = $semana
        AND YEAR((created_at - INTERVAL (DAYOFWEEK(created_at) % 7) DAY)) = $year";

        $responses = QuestionResponse::with([
            'selectedOption',
            'question',
            'response' => function ($q) use ($weekCondition) {
                $q->whereRaw($weekCondition)
                    ->whereHas('form', function ($q) {
                        $q->where('_qualified', 1);
                    });
            },
            'response.form'
        ])
        ->whereHas('selectedOption', function ($q) {
            $q->where('_correct', 0);
        })
        ->whereHas('response', function ($q) use ($weekCondition) {
            $q->whereRaw($weekCondition)
                ->whereHas('form', function ($q) {
                    $q->where('_qualified', 1);
                });
        })
        ->get();

        $userResponses = [];

        foreach ($responses as $response) {
            $conditions = json_decode(json_decode($response['condition']));
            if (!$conditions) continue;

            $users = [];
            $quali = "";
            $observacion = "No hay observación";

            foreach ($conditions as $condition) {
                if (!isset($condition->response)) continue;

                if (is_string($condition->response)) {
                    $observacion = $condition->response;
                    continue;
                }

                if (is_array($condition->response)) {
                    foreach ($condition->response as $qualified) {
                        if (is_object($qualified) && isset($qualified->col)) {
                            $users[] = $qualified->col;
                            $quali = $qualified->qualified;
                        } elseif (is_numeric($qualified)) {
                            $users[] = $qualified;
                        }
                    }
                }
            }

            if (!in_array($userId, $users)) continue;

            $createdAt = $response['response']['created_at'];


            $userResponses[] = [
                "response" => $response['id'],
                "form" => $response['response']['form']['name'] ?? "N/A",
                "question" => $response['question']['question'] ?? "N/A",
                "_retained" => $response['question']['_retained'] ?? 0,
                "text" => $response['text'] ?? "",
                "qualified" => $quali,
                "fecha_hora" => $createdAt->format('Y-m-d H:i:s'),
                "observacion" => $observacion
            ];
        }

        return $userResponses;
    }

    public function compareUserClassification($userId) {
        $date = now()->format('Y-m-d');
        $fechas = Fecha::select('*',
        DB::raw(' WEEK((fecha - INTERVAL (DAYOFWEEK(fecha) % 7) DAY), 7) AS week'),
        DB::raw(' YEAR(fecha) AS anio')
        )->orderBy('fecha','ASC')->get();
        $filtradas = $fechas->filter(fn($item) => $item->fecha == $date);
        $oing = $filtradas->last();

        $user = User::with([
            'classification.store.store',
            'classification.store.clasification.bonuses',
            'classification.classification',
            'rol.area'
        ])->where('id', $userId)->first();

        if (!$user || !$user->classification || !$user->classification->classification) {
            return response()->json(["error" => "No se encontró la clasificación del usuario"], 404);
        }

        $dbClassification = $user->classification->classification->id;
        $request = new Request(["id" => $userId, "RC_id" => $user->RC_id]);
        $calculatedData = $this->getCalculateClassUser($request);
        $calculatedClass = $calculatedData->original['resumen'][$oing->week]['classification'] ?? null;

        if ($calculatedClass === null) {
            return response()->json(["error" => "No se pudo calcular la clasificación del usuario"], 500);
        }

        // Comparar ambas clasificaciones
        $isMatch = $dbClassification === $calculatedClass;

        return response()->json([
            "user" => $user,
            "calculate" => $calculatedData->original['resumen'][$oing->week],
            "classAct" => $dbClassification,
            "classCal" => $calculatedClass,
            "match" => $isMatch
        ]);
    }

}
