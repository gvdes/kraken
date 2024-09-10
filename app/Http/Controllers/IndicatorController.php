<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Form;
use App\Models\FormResponsible;
use App\Models\FormType;
use App\Models\FormQuestion;
use App\Models\QuestionType;
use App\Models\QuestionOption;
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
        $form = $request->form;
        $question = $request->question['quest'];
        $condition = $request->condition;
        $busform = Form::find($form['id']);
        if($busform){
            $quest = [
                '_form'=>$busform->id,
                'question'=>$question['question'],
                '_type'=>$question['_type']['id'],
            ];
            $insque = FormQuestion::insertGetId($quest);
            if($insque){
                if($question['_type']['id'] == 2){
                    $optsquest = $request->question['opts'];
                    foreach($optsquest as $option){
                        if($condition['quest']['state']){
                            if($option == $condition['quest']['condition'] ){
                                $insopt = [
                                    "_question"=>$insque,
                                    "option"=>$option,
                                    "condition"=>json_encode($condition)
                                ];
                            }else{
                                $insopt = [
                                    "_question"=>$insque,
                                    "option"=>$option,
                                ];
                            }
                        }else{
                            $insopt = [
                                "_question"=>$insque,
                                "option"=>$option,
                            ];
                        }
                        $inopqu = QuestionOption::insert($insopt);
                    }
                    $pregunta = FormQuestion::with(['type'])->find($insque);
                    return response()->json($pregunta,200);
                }
                $pregunta = FormQuestion::with(['type'])->find($insque);
                return response()->json($pregunta,200);
            }else{
                return response()->json(["Message"=>"No se agrego la pregunta :("],500);
            }
        }else{
            return response()->json(["Message"=>"No se encuentra el formulario"],404);
        }
    }

    public function getFormResp($form){
        $getform = Form::with('type','responsible','user','question.type','question.options')->where('id',$form)->first();
        $users = User::with('store:id,name','rol.area','state','useStore','apps')->get();
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

}
