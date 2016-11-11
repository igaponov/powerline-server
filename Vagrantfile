# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|

  config.vm.box = "civix"
  config.vm.box_url = "https://cloud-images.ubuntu.com/vagrant/trusty/current/trusty-server-cloudimg-amd64-vagrant-disk1.box"
  config.vm.network "forwarded_port", guest: 80, host: 8080
  config.vm.network "private_network", ip: "192.168.10.100"
  config.vm.synced_folder ".", "/vagrant", type: "nfs"

  config.vm.provision "ansible" do |ansible|
    ansible.inventory_path = "backend/deployment/ansible/inventory/all"
    ansible.playbook = "backend/deployment/ansible/vagrant.yml"
    ansible.sudo = true
    ansible.limit = "vagrant"
  end

  config.vm.provider "virtualbox" do |v|
    v.memory = 1024
  end
end
