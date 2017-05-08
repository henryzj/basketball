<?php

/**
 * 路由相关
 *
 * @author JiangJian <silverd@sohu.com>
 */

class Core_Route
{
    public static function restful(Yaf_Request_Abstract $request, array $rules)
    {
        if (! $rules) {
            return false;
        }

        $method = strtoupper($request->getMethod());

        $rulesGroup = [];

        foreach ($rules as $rule) {
            list($module, $controller, $action) = explode('/', trim($rule[2], '/'));
            $rulesGroup[$rule[0]][] = [
                'match'      => $rule[1],
                'module'     => $module,
                'controller' => $controller,
                'action'     => $action,
            ];
        }

        if (! isset($rulesGroup[$method]) || ! $rulesGroup[$method]) {
            return false;
        }

        $router = Yaf_Dispatcher::getInstance()->getRouter();

        $i = 0;

        foreach ($rulesGroup[$method] as $rule) {
            $router->addRoute('route_' . (++$i), new Yaf_Route_Rewrite(
                $rule['match'],
                [
                    'module'     => $rule['module'],
                    'controller' => $rule['controller'],
                    'action'     => $rule['action'],
                ]
            ));
        }

        // Content-type 必须是 application/x-www-form-urlencoded
        if (in_array($method, ['PUT', 'PATCH', 'DELETE'])) {
            if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded') {
                parse_str(file_get_contents('php://input'), $params);
                $params && $request->setParam($params);
            }
        }
    }
}