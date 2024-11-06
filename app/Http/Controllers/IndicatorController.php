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
        $question->save();
        $options = $request->options;
        $conditions = $request->condition;
        $matchedCond = [];
        if(count($options) > 0){
            foreach($options as $option){
                $resop [] = $option['option'];
                $opts = QuestionOption::updateOrCreate(
                    ['_question'=>$id,'option'=>$option['option']],
                    ['_question'=>$id,'option'=>$option['option'],'condition'=>$option['condition']],
                );

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

    public function getFormResp($form){
        $getform = Form::with('type','responsible','user','question.type','question.options')->where('id',$form)->first();
        $users = User::all();
        if($users){
            $res = [
                "usuarios"=>$users,
                "formulario"=>$getform
            ];
            return response()->json($res,200);
        }else{
            return response()->json("No hay ningun Usuario",404);
        }
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
        $user = $request->_user;
        $form = $request->_form;
        $questions = $request->question;
        return $questions;
        foreach($questions as $question){
            return $question;
        }
        if ($request->hasFile('files')) {
            $folderName = uniqid();
            $folderPath = public_path('multimedia/' . $folderName);
            $files = $request->file('files');
            $saved = [];
            foreach($files as $file){
                $fileName=$file->getClientOriginalName();
                $file->move($folderPath, $fileName);
            }
            return $saved;
        }
    }
}
