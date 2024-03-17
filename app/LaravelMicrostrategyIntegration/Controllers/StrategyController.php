<?php

namespace App\LaravelMicrostrategyIntegration\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use App\LaravelMicrostrategyIntegration\Facades\Strategy;


class StrategyController extends Controller
{
    //auth token fornecido ao performar login na API do microstrategy
    private $XMSTRAuthToken;
    //valor fixo  que identifica  o projeto/empresa
    private $ProjetctId;
    //cokies de autenticaçao e controle de sessão do strategy é importante  envia-los em todos as requisiçoes
    //sem eles a  api nao reconhece a validade do token e retorna não autorizado ela trata como uma sessão expirada
    private $cookiese;

    //metodo construtor  inicializando  project id considerar alterar a injeçao de dependencia para criar mock e testes unitarios
    public function __construct(String $ProjetctId){
        $this->ProjetctId = $ProjetctId;
    }

    /**
     * Summary of Sauth
     * @param mixed $usuario
     * @param mixed $senha
     * @return bool
     * metodo de authenticaçao
     * travado em login mode 1 para simplicidade
     */
    public function Sauth($usuario,$senha){

    //construçao do corpo da requisiçao  verificar a posteriadade se é possivel  melhorar a formatação sem prejudicar os requisitos de corpo
        $body='{"username":"'. "$usuario".'",
            '.
 '"password":"'."$senha".'",
  "loginMode": 1
    }';
   $response= Strategy::withBody($body)->post('auth/login');


        $this->XMSTRAuthToken = $response->header('X-MSTR-AuthToken');
        $this->cookiese = $response->cookies;

        /** implementar tratativa do retorno para identificar   falhas de login
         * considerar criar expetions customizadas para lançar em caso de falha
         * retorna true para facilitar a logica em controlers que utilizem esse metodo
         * */
        return true;

    }

    /**
     * Summary of getInstance
     * @param string $reportID
     * @return mixed
     * inicia uma instancia de um relatorio e retorna ID
     * considerar alterar nome do metodo para deixar claro  a versao da API utilizada (vs1)
     * report id é obtido no pront do strategylib e depende  do projeto e do relatorio desejado
     * wssa função só deve ser utilizada em relatorios que possuam prompts para economia de memoria,
     * realizar um get instances em um relatorio sem prompt para depois executar o relatorio é disperdicio de recursos
     */
    public function getInstance(String $reportID)
    {
        // Obtendo os cookies
        $cookies = $this->mapCookies($this->cookiese);

        //requisiçao
        $response = Strategy::withHeaders([
            'X-MSTR-AuthToken' => $this->XMSTRAuthToken,
            'X-MSTR-ProjectID' => $this->ProjetctId,
            'reportId' => $reportID,
            'Cookie' => $cookies // Enviando os cookies no cabeçalho 'Cookie'
        ])->post("reports/$reportID/instances");
        /**
         * Implementar tratamento de erros na response
         **/
        return $response->json("instanceId");
    }

    /**
     * Summary of getInstanceNoPrompt
     * @param string $reportID
     * @return array
     *
     *inicia uma instancia de um relatorio e retorna um array multidimencional no formato:
     *[linha1 [coluna1[valorForm1],[valorform2],  [coluna2[valorForm1],[valorform2]], [false]],
     *linha2 [coluna1[valorForm1],[valorform2],  [coluna2[valorForm1],[valorform2]]]
     *considerar alterar nome do metodo para deixar claro  a versao da API utilizada (vs1) e que se trata de um instancia de relatorio
     *report id é obtido no front do strategylib e depende  do projeto e do relatorio desejado
     *essa função só deve ser utilizada em relatorios nao possuam prompts
     *
     */
    public function getInstanceNoPrompt(string $reportID)
    {
        // Obtendo os cookies da forma correta
        $cookies = $this->mapCookies($this->cookiese);
        //requisiçao
        $response = Strategy::withHeaders([
            'X-MSTR-AuthToken' => $this->XMSTRAuthToken,
            'X-MSTR-ProjectID' => $this->ProjetctId,
            'reportId' => $reportID,
            'Cookie' => $cookies // Enviando os cookies no cabeçalho 'Cookie'
        ])->post("reports/$reportID/instances");
        //chama funçao que retorna qtd de atributos + metrics
        $profundidade = $this->DescobrirProfundidade($response->json("result"));

        //funçao monta o array
        $tabela = $this->resultToArray($response->json("result"),$profundidade);
        /**
         * implementar diferentes tratamentos de erro possiveis, dividir codificaçao para erros internos de implementaçao
         * e erros de parametros (relatorio  nao encontrado, falta de acesso, etc)
         **/
        return $tabela;

    }

    /**
     * Summary of sendPrompt
     * @param string $reportID
     * @param string $instanceID
     * @param mixed $Prompts
     */
    public function sendPrompt(String $reportID, String $instanceID, $Prompts){
        $cookies= $this->mapCookies($this->cookiese);

        $response = Strategy::withHeaders([
            'accept'=>"*/*",
            //'reportId' => $reportID,
           // 'instanceId'=>$instanceID,
            //'Cookie' => $cookies // Enviando os cookies no cabeçalho 'Cookie'
        ])->withBody($Prompts)->put("reports/$reportID/instances/$instanceID/prompts/answers");
        return true;

    }

    public function getReport(string $reportID, string $instanceID){
        // Obtendo os cookies
        $cookies = $this->mapCookies($this->cookiese);

        //requisiçao
        $response = Strategy::withHeaders([
            'accept' => "*/*",
           // 'X-MSTR-AuthToken' => $this->XMSTRAuthToken,
           // 'X-MSTR-ProjectID' => $this->ProjetctId,
           // 'reportId' => $reportID,
            //'instanceId' => $instanceID,
           // 'Cookie' => $cookies // Enviando os cookies no cabeçalho 'Cookie'
        ])->get("reports/$reportID/instances/$instanceID");
        /**
         * Implementar tratamento de erros na response
         **/

        $profundidade = $this->DescobrirProfundidade($response->json("result"));

        //funçao monta o array
        $tabela = $this->resultToArray($response->json("result"), $profundidade);
        /**
         * implementar diferentes tratamentos de erro possiveis, dividir codificaçao para erros internos de implementaçao
         * e erros de parametros (relatorio  nao encontrado, falta de acesso, etc)
         **/

        return $tabela;

    }

    /**
     * Summary of logout
     * @return bool
     */
    public function logout(){
        $cookies = $this->mapCookies($this->cookiese);
        $response=Strategy::withHeaders([
            'X-MSTR-AuthToken' => $this->XMSTRAuthToken,
            'Cookie' => $cookies
        ])->post("auth/logout");
        return true;
        /** implementar tratativa do retorno para identificar   falhas de login
         * considerar criar expetions customizadas para lançar em caso de falha
         * retorna true para facilitar a logica em controlers que utilizem esse metodo
         * */
    }

    /**
     * Summary of mapCookies
     * @param mixed $cookies
     * @return string
     * função auxiliar interna converte objeto da requisiçao em  string
     */
    private function mapCookies($cookies)
    {
        return collect($cookies)->map(function ($cookie) {
            return $cookie->getName() . '=' . $cookie->getValue();
        })->implode('; ');
    }
    /**
     * Summary of resultToArray
     * @param mixed $result  result bruto da requisiçao do relatorio
     * @param int $profundidadeTOTAL é a quantidade de aninhamentos de children no relatorio
     * @return array
     * #[missing metricas()]
     * para cada data root (linha) intera a quantidade de colunas  chamando funçao recursiva para obter dados
     * cada conjunto de formvalues(array) é  anexado como coluna ao array de linha, que por sua vez ao fim de uma iteraçao e anexado ao array de dados  (tabela )
     * que deve ser retornado
     *
     * #[nessesario implementar a construçao dos cabeçalhos, o anexo das metricas]
     * é muito provavel que exista uma maneira melhor e mais eficiente para alterar a estrutura do array para facilitar o uso da classe
     *
     */
    private function resultToArray($result ,int $profundidadeTOTAL) :array
    {
        //pega cabeçalho das metricas sera utilizado quando a funçao estive inteiramente implementada
        $metricas = count($result['definition']['metrics']);
        //array multidimencional a ser retornado
        $dados[]=[];

        //faz a iteraçao das linhas do relatorio cada conjunto de children[0] sera iterado
        foreach($result['data']['root']['children'] as $linha){
            //variavel de controle do fluxo de colunas
            $i = 0;
            //inicializa array das linhas a serem adicionados na matriz
            $dadolinha= Array();
            //por algum motivo os dados desse array persistem apos ciclo de iteraçoes mesmo a variavel tendo sido inicializado apos o inicio do loop
            //a linha abaixo limpa o array
            $dadolinha= array_diff($dadolinha, $dadolinha);
            //iteraçao das colunas, enquanto a quantidade de colunas(childrens for > ou igual a o numero da iteraçao o fluxo continua
            //necessario verificar a necessidade desse exato operador de comparaçao pois a o comportamento de duplicidade do  valor false que deveria encerrar a linha
            //no array final
            while ($profundidadeTOTAL >= $i) {
                //funçao recursiva que retorna o dado da proxima coluna
                array_push($dadolinha,$this->linhaTabela($linha['children'], 0, $i));
                $i++;
            }
            //montando array final
            array_push($dados , $dadolinha);
        }
        return $dados;
        //existe algum erro pra tratar aqui?
    }

    /**
     * Summary of linhaTabela
     * @param mixed $children //json contendo os dados do nivel atual da arvore e posteriores
     * @param int $started //nivel hierarquico da coluna atual iniciado em 0 na chamada original
     * @param int $desired //coluna que desejamos o valor
     * @return mixed
     */
    private function linhaTabela($children,int $started,int $desired){
        //ja estamos na coluna desejada?
        if($started == $desired){
            //sim, devolve ela
            return $children[0]['element']['formValues'];

        } else{
            //não
            //almenta o nivel da hierarquia
            $started++;
            try {
                //aplicamos recursividade e procuramos no proximo nivel
                return $this->linhaTabela($children[0]['children'], $started, $desired);
            }catch(\Exception $e){
                //comportamento estranho para metricas, ainda nao tenho certeza mas acho que a logica para implementar as metricas vira aqui
                return false;
            }
            }

    }

    /**
     * Summary of DescobrirProfundidade
     * @param mixed $result
     * @return int
     * descobre o tamanho da arvore de atributos
     */
    private function DescobrirProfundidade($result):int
    {
        //legado

        /*$profundidade = 0;
        foreach($result["definition"]["attributes"] as $atributo){
            $profundidade += count($atributo["forms"]);
        }*/

        //atual
        $profundidade = count($result["definition"]["attributes"]);
        return $profundidade;

    }
}
