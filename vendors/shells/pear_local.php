<?php
  /**
   * PEAR Local install plugin for CakePHP
   *
   * This plugin is inspired by pear-local command (Ethna), see http://ethna.jp/
   *
   * @copyright Copyright 2010, 101000code/101000LAB (http://code.101000lab.org/)
   * @author Kenichirou Oyama
   */
class PearLocalShell extends Shell {
    var $tasks = array();
    var $channel;
    var $baseDir;

    /**
     * main
     *
     * @return
     */
    function main(){
        $this->_setArgs();

        if (!file_exists($this->baseDir . 'pear.conf')) {
            $this->init();
        }

        if (!empty($this->args)) {
            $command = 'pear -c ' . $this->baseDir . 'pear.conf ' . implode(' ' , $this->args);
            system($command);
        }

        $this->writeBootstrap();
    }

    /**
     * init
     *
     * @return
     */
    function init(){
        $this->out(__('PEAR Initialize..', true));

        $this->_setArgs();

        $command = 'pear config-create ' . $this->baseDir . ' ' . $this->baseDir . 'pear.conf';
        system($command);
        $command = 'pear -c ' . $this->baseDir . 'pear.conf channel-discover ' . $this->channel;
        system($command);
        $command = 'pear -c ' . $this->baseDir . 'pear.conf config-set bin_dir ' . $this->baseDir . 'pear' . DS . 'bin';
        system($command);
        $command = 'pear -c ' . $this->baseDir . 'pear.conf config-set php_dir ' . $this->baseDir . 'pear' . DS;
        system($command);
        $command = 'pear -c ' . $this->baseDir . 'pear.conf config-set data_dir ' . $this->baseDir . 'pear' . DS . 'data';
        system($command);
        $command = 'pear -c ' . $this->baseDir . 'pear.conf config-set cache_dir ' . TMP . 'pear';
        system($command);
        $command = 'pear -c ' . $this->baseDir . 'pear.conf config-set doc_dir ' . $this->baseDir . 'pear' . DS . 'data';
        system($command);
        $command = 'pear -c ' . $this->baseDir . 'pear.conf config-set download_dir ' . DS . 'tmp' . DS . 'pear' . DS . 'build';
        system($command);
        $command = 'pear -c ' . $this->baseDir . 'pear.conf config-set ext_dir' . $this->baseDir . 'pear' . DS . 'ext';
        system($command);
        $command = 'pear -c ' . $this->baseDir . 'pear.conf config-set test_dir ' . $this->baseDir . 'pear' . DS . 'test';
        system($command);

        $code = '<?php set_include_path(dirname(__FILE__) . PATH_SEPARATOR . get_include_path());';
        $fp  =  new File($this->baseDir . 'pear' . DS . 'pear_init.php');
        $fp->write($code);
        $fp->close();

        return true;
    }

    /**
     * writeBootstrap
     * write bootstrap
     *
     * @param $code
     * @return
     */
    function writeBootstrap(){
        $this->out(__('Writeing bootstrap.php ', true) . '...');
        $bootstrapPath = APP_PATH . 'config/bootstrap.php';
        $fp  =  new File($bootstrapPath);
        $out = $fp->read();

        $imports = array();
        $imports[] = 'App::import(\'Vendor\', \'pear\' . DS . \'pear_init\');';

        if (!empty($this->args)) {
            foreach ($this->args as $value) {
                if (preg_match('/^[A-Z][a-zA-Z2]+[a-zA-Z_]*$/', $value)) {
                    $this->out(__('Set ' . $value, true) . '...');
                    $path = str_replace('_' , DS , $value);
                    $imports[] = 'App::import(\'Vendor\', \'' . $value . '\', array(\'file\' => \'' . $path . '.php\'));';
                }
                if (preg_match('/^[a-zA-Z.-]+\/[A-Z][a-zA-Z2]+[a-zA-Z_]*$/', $value)) {
                    $this->out(__('Set ' . $value, true) . '...');
                    $path = str_replace('_' , DS , preg_replace('/^.*\//', '', $value));
                    $imports[] = 'App::import(\'Vendor\', \'' . preg_replace('/^.*\//', '', $value) . '\', array(\'file\' => \'' . $path . '.php\'));';
                }
            }
        }

        foreach ($imports as $code) {
            if (strpos($out, $code)) {
                continue;
            }

            if (preg_match('/\?>/', $out)) {
                $out = preg_replace('/\?>/', $code  . ' // pear_local auto set' . "\n?>", $out);
                $fp->write($out);
            } else {
                $fp->append("\n" . $code . ' // pear_local auto set');
            }
        }
    }

    /**
     * _setArgs
     * set channel and baseDir
     *
     * @return
     */
    function _setArgs(){
        $this->channel = empty($this->params['c']) ? 'pear.php.net' : $this->params['c'];
        $this->baseDir = empty($this->params['b']) ? APP_PATH . 'vendors' . DS : $this->params['b'];
    }

    /**
     * _welcome
     *
     * @return
     */
    function _welcome(){
        $this->out(__('PEAR Local for CakePHP', true));
        $this->hr();
    }
  }